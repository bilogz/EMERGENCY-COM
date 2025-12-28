<?php
/**
 * AI-Powered Auto Warning System API
 * Analyzes weather and earthquake data to automatically send warnings
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    switch ($action) {
        case 'getSettings':
            getAISettings();
            break;
            
        case 'test':
            sendTestWarning();
            break;
            
        case 'check':
            checkAndSendWarnings();
            break;
            
        default:
            saveAISettings();
            break;
    }
} catch (Exception $e) {
    error_log("AI Warnings API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function getAISettings() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM ai_warning_settings ORDER BY id DESC LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        // Return default settings
        echo json_encode([
            'success' => true,
            'settings' => [
                'ai_enabled' => false,
                'ai_check_interval' => 30,
                'wind_threshold' => 60,
                'rain_threshold' => 20,
                'earthquake_threshold' => 5.0,
                'warning_types' => 'flooding,landslide,typhoon,earthquake',
                'monitored_areas' => 'Quezon City\nManila\nMakati',
                'ai_channels' => 'sms,email,pa'
            ]
        ]);
    } else {
        echo json_encode(['success' => true, 'settings' => $settings]);
    }
}

function saveAISettings() {
    global $pdo;
    
    // Create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS ai_warning_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ai_enabled TINYINT(1) DEFAULT 0,
        ai_check_interval INT DEFAULT 30,
        wind_threshold DECIMAL(5,2) DEFAULT 60,
        rain_threshold DECIMAL(5,2) DEFAULT 20,
        earthquake_threshold DECIMAL(3,1) DEFAULT 5.0,
        warning_types TEXT DEFAULT NULL,
        monitored_areas TEXT DEFAULT NULL,
        ai_channels TEXT DEFAULT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $aiEnabled = isset($_POST['ai_enabled']) ? 1 : 0;
    $aiCheckInterval = intval($_POST['ai_check_interval'] ?? 30);
    $windThreshold = floatval($_POST['wind_threshold'] ?? 60);
    $rainThreshold = floatval($_POST['rain_threshold'] ?? 20);
    $earthquakeThreshold = floatval($_POST['earthquake_threshold'] ?? 5.0);
    $warningTypes = implode(',', $_POST['warning_types'] ?? []);
    $monitoredAreas = $_POST['monitored_areas'] ?? '';
    $aiChannels = implode(',', $_POST['ai_channels'] ?? []);
    
    // Check if settings exist
    $stmt = $pdo->query("SELECT id FROM ai_warning_settings ORDER BY id DESC LIMIT 1");
    $existing = $stmt->fetch();
    
    if ($existing) {
        $stmt = $pdo->prepare("UPDATE ai_warning_settings SET 
            ai_enabled = ?, 
            ai_check_interval = ?, 
            wind_threshold = ?, 
            rain_threshold = ?, 
            earthquake_threshold = ?, 
            warning_types = ?, 
            monitored_areas = ?, 
            ai_channels = ?
            WHERE id = ?");
        $stmt->execute([
            $aiEnabled, $aiCheckInterval, $windThreshold, $rainThreshold,
            $earthquakeThreshold, $warningTypes, $monitoredAreas, $aiChannels,
            $existing['id']
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO ai_warning_settings 
            (ai_enabled, ai_check_interval, wind_threshold, rain_threshold, 
             earthquake_threshold, warning_types, monitored_areas, ai_channels) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $aiEnabled, $aiCheckInterval, $windThreshold, $rainThreshold,
            $earthquakeThreshold, $warningTypes, $monitoredAreas, $aiChannels
        ]);
    }
    
    echo json_encode(['success' => true, 'message' => 'AI settings saved successfully']);
}

function sendTestWarning() {
    global $pdo;
    
    // Create a test warning
    $title = "Test AI Warning - Dangerous Weather Detected";
    $content = "This is a test warning from the AI Auto Warning System. If you receive this, the system is working correctly.";
    
    // Insert into automated_warnings
    $stmt = $pdo->prepare("INSERT INTO automated_warnings 
        (source, type, title, content, severity, status) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['ai', 'test', $title, $content, 'high', 'published']);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Test warning created successfully',
        'warning_id' => $pdo->lastInsertId()
    ]);
}

function checkAndSendWarnings() {
    global $pdo;
    
    // Get AI settings
    $stmt = $pdo->query("SELECT * FROM ai_warning_settings WHERE ai_enabled = 1 ORDER BY id DESC LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings || !$settings['ai_enabled']) {
        echo json_encode(['success' => false, 'message' => 'AI warnings are disabled']);
        return;
    }
    
    $warnings = [];
    
    // Check weather conditions (using OpenWeatherMap API)
    $warnings = array_merge($warnings, checkWeatherConditions($settings));
    
    // Check earthquake conditions
    $warnings = array_merge($warnings, checkEarthquakeConditions($settings));
    
    // Check flooding/landslide risks
    $warnings = array_merge($warnings, checkFloodingLandslideRisks($settings));
    
    // Save warnings to database
    foreach ($warnings as $warning) {
        $stmt = $pdo->prepare("INSERT INTO automated_warnings 
            (source, type, title, content, severity, status) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'ai',
            $warning['type'],
            $warning['title'],
            $warning['content'],
            $warning['severity'],
            'published'
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'warnings_generated' => count($warnings),
        'warnings' => $warnings
    ]);
}

function checkWeatherConditions($settings) {
    global $pdo;
    $warnings = [];
    
    $windThreshold = floatval($settings['wind_threshold'] ?? 60); // km/h
    $rainThreshold = floatval($settings['rain_threshold'] ?? 20); // mm/hour
    $warningTypes = explode(',', $settings['warning_types'] ?? '');
    
    // Get OpenWeatherMap API key
    $stmt = $pdo->query("SELECT api_key FROM integration_settings WHERE source = 'pagasa' LIMIT 1");
    $apiKeyRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $apiKey = $apiKeyRow['api_key'] ?? '';
    
    if (empty($apiKey)) {
        return $warnings; // No API key configured
    }
    
    // Check Quezon City weather (primary monitoring area)
    $lat = 14.6488;
    $lon = 121.0509;
    $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";
    
    $response = @file_get_contents($url);
    if ($response) {
        $weatherData = json_decode($response, true);
        
        if (isset($weatherData['wind']['speed'])) {
            $windSpeedMs = floatval($weatherData['wind']['speed']); // m/s
            $windSpeedKmh = $windSpeedMs * 3.6; // Convert to km/h
            
            if ($windSpeedKmh >= $windThreshold && in_array('typhoon', $warningTypes)) {
                $warnings[] = [
                    'type' => 'typhoon',
                    'title' => "High Wind Warning - {$windSpeedKmh} km/h",
                    'content' => "Dangerous wind speeds detected in Quezon City ({$windSpeedKmh} km/h). Take precautions and secure loose objects.",
                    'severity' => $windSpeedKmh >= 100 ? 'critical' : ($windSpeedKmh >= 80 ? 'high' : 'medium')
                ];
            }
        }
        
        if (isset($weatherData['rain']['1h'])) {
            $rainfall = floatval($weatherData['rain']['1h']); // mm in last hour
            
            if ($rainfall >= $rainThreshold) {
                if (in_array('flooding', $warningTypes)) {
                    $warnings[] = [
                        'type' => 'flooding',
                        'title' => "Heavy Rainfall Alert - {$rainfall}mm/hour",
                        'content' => "Heavy rainfall detected in Quezon City ({$rainfall}mm/hour). Risk of flooding in low-lying areas. Avoid flood-prone areas.",
                        'severity' => $rainfall >= 50 ? 'critical' : ($rainfall >= 30 ? 'high' : 'medium')
                    ];
                }
                
                if (in_array('landslide', $warningTypes) && $rainfall >= 30) {
                    $warnings[] = [
                        'type' => 'landslide',
                        'title' => "Landslide Risk Alert",
                        'content' => "Heavy rainfall ({$rainfall}mm/hour) increases landslide risk in hilly areas of Quezon City. Residents near slopes should be alert.",
                        'severity' => $rainfall >= 50 ? 'critical' : 'high'
                    ];
                }
            }
        }
    }
    
    return $warnings;
}

function checkEarthquakeConditions($settings) {
    $warnings = [];
    $threshold = floatval($settings['earthquake_threshold'] ?? 5.0);
    
    // Check recent earthquakes from USGS API
    $url = "https://earthquake.usgs.gov/fdsnws/event/1/query?format=geojson&starttime=" . 
           date('Y-m-d', strtotime('-1 day')) . 
           "&minmagnitude=" . $threshold . 
           "&maxlatitude=21.0&minlatitude=4.5&maxlongitude=127.0&minlongitude=116.0";
    
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['features']) && count($data['features']) > 0) {
            foreach ($data['features'] as $feature) {
                $mag = floatval($feature['properties']['mag'] ?? 0);
                if ($mag >= $threshold) {
                    $place = $feature['properties']['place'] ?? 'Philippines';
                    $warnings[] = [
                        'type' => 'earthquake',
                        'title' => "Earthquake Alert - Magnitude {$mag}",
                        'content' => "A magnitude {$mag} earthquake was detected near {$place}. Please take necessary precautions.",
                        'severity' => $mag >= 6.0 ? 'critical' : ($mag >= 5.5 ? 'high' : 'medium')
                    ];
                }
            }
        }
    }
    
    return $warnings;
}

function checkFloodingLandslideRisks($settings) {
    global $pdo;
    $warnings = [];
    $monitoredAreas = explode("\n", $settings['monitored_areas'] ?? '');
    $warningTypes = explode(',', $settings['warning_types'] ?? '');
    
    $rainThreshold = floatval($settings['rain_threshold'] ?? 20);
    
    // Get OpenWeatherMap API key
    $stmt = $pdo->query("SELECT api_key FROM integration_settings WHERE source = 'pagasa' LIMIT 1");
    $apiKeyRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $apiKey = $apiKeyRow['api_key'] ?? '';
    
    if (empty($apiKey)) {
        return $warnings;
    }
    
    // Area coordinates (simplified - in production, use geocoding)
    $areaCoords = [
        'Quezon City' => ['lat' => 14.6488, 'lon' => 121.0509],
        'Manila' => ['lat' => 14.5995, 'lon' => 120.9842],
        'Makati' => ['lat' => 14.5547, 'lon' => 121.0244],
    ];
    
    foreach ($monitoredAreas as $area) {
        $area = trim($area);
        if (empty($area) || !isset($areaCoords[$area])) continue;
        
        $coords = $areaCoords[$area];
        $url = "https://api.openweathermap.org/data/2.5/weather?lat={$coords['lat']}&lon={$coords['lon']}&appid={$apiKey}&units=metric";
        
        $response = @file_get_contents($url);
        if ($response) {
            $weatherData = json_decode($response, true);
            
            if (isset($weatherData['rain']['1h'])) {
                $rainfall = floatval($weatherData['rain']['1h']);
                
                if ($rainfall >= $rainThreshold) {
                    if (in_array('flooding', $warningTypes)) {
                        $warnings[] = [
                            'type' => 'flooding',
                            'title' => "Flooding Risk Alert - {$area}",
                            'content' => "Heavy rainfall detected in {$area} ({$rainfall}mm/hour). Risk of flooding in low-lying areas. Residents should prepare for possible evacuation.",
                            'severity' => $rainfall >= 50 ? 'critical' : ($rainfall >= 30 ? 'high' : 'medium')
                        ];
                    }
                    
                    if (in_array('landslide', $warningTypes) && $rainfall >= 30) {
                        $warnings[] = [
                            'type' => 'landslide',
                            'title' => "Landslide Risk Alert - {$area}",
                            'content' => "Heavy rainfall ({$rainfall}mm/hour) increases landslide risk in hilly areas of {$area}. Residents near slopes and embankments should be alert and consider evacuation if necessary.",
                            'severity' => $rainfall >= 50 ? 'critical' : 'high'
                        ];
                    }
                }
            }
        }
    }
    
    return $warnings;
}

