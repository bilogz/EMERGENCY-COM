<?php
/**
 * AI-Powered Auto Warning System API
 * Analyzes weather and earthquake data using Gemini AI to automatically send warnings
 * Automatically sends SMS/Email/Push notifications to subscribed users
 * 
 * SETUP CRON JOB for automatic checking:
 * Add this to your crontab (crontab -e):
 * */30 * * * * curl -s "http://your-domain.com/EMERGENCY-COM/ADMIN/api/ai-warnings.php?action=check&cron=true" > /dev/null 2>&1
 * 
 * This will check for dangerous conditions every 30 minutes and automatically send alerts.
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once 'secure-api-config.php';
require_once 'alert-translation-helper.php';

session_start();

// Check if user is logged in (except for automated cron jobs)
$isCronJob = isset($_GET['cron']) && $_GET['cron'] === 'true';
if (!$isCronJob && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
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
                'gemini_api_key' => '',
                'ai_enabled' => false,
                'ai_check_interval' => 30,
                'wind_threshold' => 60,
                'rain_threshold' => 20,
                'earthquake_threshold' => 5.0,
                'warning_types' => 'heavy_rain,flooding,earthquake,strong_winds,tsunami,landslide,thunderstorm,ash_fall,fire_incident,typhoon',
                'monitored_areas' => 'Quezon City\nManila\nMakati',
                'ai_channels' => 'sms,email,pa'
            ]
        ]);
    } else {
        // Mask API key for security (only show last 4 characters)
        if (!empty($settings['gemini_api_key'])) {
            $apiKey = $settings['gemini_api_key'];
            $settings['gemini_api_key'] = str_repeat('*', max(0, strlen($apiKey) - 4)) . substr($apiKey, -4);
        }
        echo json_encode(['success' => true, 'settings' => $settings]);
    }
}

function saveAISettings() {
    global $pdo;
    
    // Create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS ai_warning_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        gemini_api_key VARCHAR(255) DEFAULT NULL,
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
    
    $geminiApiKey = $_POST['gemini_api_key'] ?? '';
    $aiEnabled = isset($_POST['ai_enabled']) ? 1 : 0;
    $aiCheckInterval = intval($_POST['ai_check_interval'] ?? 30);
    $windThreshold = floatval($_POST['wind_threshold'] ?? 60);
    $rainThreshold = floatval($_POST['rain_threshold'] ?? 20);
    $earthquakeThreshold = floatval($_POST['earthquake_threshold'] ?? 5.0);
    $warningTypes = implode(',', $_POST['warning_types'] ?? []);
    $monitoredAreas = $_POST['monitored_areas'] ?? '';
    $aiChannels = implode(',', $_POST['ai_channels'] ?? []);
    
    // Check if settings exist
    $stmt = $pdo->query("SELECT id, gemini_api_key FROM ai_warning_settings ORDER BY id DESC LIMIT 1");
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Only update API key if a new one is provided (not masked)
        $updateApiKey = $geminiApiKey;
        if (empty($geminiApiKey) || (strlen($geminiApiKey) <= 4 && strpos($geminiApiKey, '*') !== false)) {
            // API key is masked or empty, keep existing one
            $updateApiKey = $existing['gemini_api_key'];
        }
        
        $stmt = $pdo->prepare("UPDATE ai_warning_settings SET 
            gemini_api_key = ?,
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
            $updateApiKey, $aiEnabled, $aiCheckInterval, $windThreshold, $rainThreshold,
            $earthquakeThreshold, $warningTypes, $monitoredAreas, $aiChannels,
            $existing['id']
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO ai_warning_settings 
            (gemini_api_key, ai_enabled, ai_check_interval, wind_threshold, rain_threshold, 
             earthquake_threshold, warning_types, monitored_areas, ai_channels) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $geminiApiKey, $aiEnabled, $aiCheckInterval, $windThreshold, $rainThreshold,
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
    
    // Use Gemini AI to analyze weather and earthquake conditions
    $warnings = array_merge($warnings, analyzeWithAI($settings));
    
    // Also check traditional thresholds as backup
    $warnings = array_merge($warnings, checkWeatherConditions($settings));
    $warnings = array_merge($warnings, checkEarthquakeConditions($settings));
    $warnings = array_merge($warnings, checkFloodingLandslideRisks($settings));
    
    // Remove duplicates based on type
    $uniqueWarnings = [];
    foreach ($warnings as $warning) {
        $key = $warning['type'] . '_' . md5($warning['title']);
        if (!isset($uniqueWarnings[$key])) {
            $uniqueWarnings[$key] = $warning;
        }
    }
    $warnings = array_values($uniqueWarnings);
    
    $sentCount = 0;
    $alertIds = [];
    
    // Save warnings to database and send notifications
    foreach ($warnings as $warning) {
        // Only send if severity is medium or higher
        if (!in_array($warning['severity'], ['medium', 'high', 'critical'])) {
            continue;
        }
        
        // Insert into automated_warnings
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
        $warningId = $pdo->lastInsertId();
        $alertIds[] = $warningId;
        
        // Create alert entry for translation
        // First, get or create category
        $categoryName = mapWarningTypeToCategory($warning['type']);
        $stmt = $pdo->prepare("SELECT id FROM alert_categories WHERE name = ? LIMIT 1");
        $stmt->execute([$categoryName]);
        $category = $stmt->fetch();
        $categoryId = $category ? $category['id'] : null;
        
        // Insert alert
        $stmt = $pdo->prepare("INSERT INTO alerts 
            (title, message, content, category_id, status, created_at) 
            VALUES (?, ?, ?, ?, 'active', NOW())");
        $stmt->execute([
            $warning['title'],
            $warning['content'],
            $warning['content'],
            $categoryId
        ]);
        $alertId = $pdo->lastInsertId();
        
        // Send notifications to subscribed users
        $sent = sendNotificationsToSubscribers($alertId, $warning, $settings);
        $sentCount += $sent;
    }
    
    echo json_encode([
        'success' => true,
        'warnings_generated' => count($warnings),
        'notifications_sent' => $sentCount,
        'alert_ids' => $alertIds,
        'warnings' => $warnings
    ]);
}

/**
 * Use Gemini AI to analyze weather and earthquake data
 */
function analyzeWithAI($settings) {
    global $pdo;
    $warnings = [];
    
    $apiKey = getGeminiApiKey();
    if (empty($apiKey)) {
        // Try to get from settings
        $apiKey = $settings['gemini_api_key'] ?? '';
    }
    
    if (empty($apiKey)) {
        error_log("Gemini API key not configured for AI analysis");
        return $warnings;
    }
    
    // Get weather data
    $weatherData = getWeatherData();
    if (empty($weatherData)) {
        return $warnings;
    }
    
    // Prepare prompt for Gemini AI
    $prompt = "Analyze the following weather and earthquake data for the Philippines. " .
              "Determine if there are any DANGEROUS conditions that require immediate emergency alerts. " .
              "Consider: heavy rain (>20mm/hour), strong winds (>60km/h), earthquakes (>5.0 magnitude), " .
              "flooding risks, landslide risks, typhoon conditions, thunderstorms, and other emergencies.\n\n" .
              "Weather Data:\n" . json_encode($weatherData, JSON_PRETTY_PRINT) . "\n\n" .
              "Warning Types Enabled: " . ($settings['warning_types'] ?? 'all') . "\n" .
              "Monitored Areas: " . ($settings['monitored_areas'] ?? 'Quezon City, Manila, Makati') . "\n\n" .
              "Respond in JSON format with this structure:\n" .
              "{\n" .
              "  \"warnings\": [\n" .
              "    {\n" .
              "      \"type\": \"typhoon|flooding|earthquake|landslide|heavy_rain|strong_winds|thunderstorm|fire_incident|tsunami|ash_fall\",\n" .
              "      \"title\": \"Brief alert title\",\n" .
              "      \"content\": \"Detailed warning message with location and recommendations\",\n" .
              "      \"severity\": \"medium|high|critical\",\n" .
              "      \"location\": \"City/Area name\",\n" .
              "      \"is_dangerous\": true\n" .
              "    }\n" .
              "  ]\n" .
              "}\n\n" .
              "Only include warnings where is_dangerous is true. Be conservative - only alert for real dangers.";
    
    try {
        $model = getGeminiModel();
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 2048,
                'topP' => 0.95,
                'topK' => 40
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $responseData = json_decode($response, true);
            
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $aiResponse = trim($responseData['candidates'][0]['content']['parts'][0]['text']);
                
                // Extract JSON from response (might have markdown code blocks)
                if (preg_match('/```json\s*(.*?)\s*```/s', $aiResponse, $matches)) {
                    $aiResponse = $matches[1];
                } elseif (preg_match('/```\s*(.*?)\s*```/s', $aiResponse, $matches)) {
                    $aiResponse = $matches[1];
                }
                
                $aiData = json_decode($aiResponse, true);
                
                if (isset($aiData['warnings']) && is_array($aiData['warnings'])) {
                    foreach ($aiData['warnings'] as $warning) {
                        if (isset($warning['is_dangerous']) && $warning['is_dangerous']) {
                            $warnings[] = [
                                'type' => $warning['type'] ?? 'general',
                                'title' => $warning['title'] ?? 'Emergency Alert',
                                'content' => $warning['content'] ?? '',
                                'severity' => $warning['severity'] ?? 'medium',
                                'location' => $warning['location'] ?? 'Quezon City'
                            ];
                        }
                    }
                }
            }
        } else {
            error_log("Gemini AI analysis failed: HTTP $httpCode");
        }
    } catch (Exception $e) {
        error_log("AI Analysis Error: " . $e->getMessage());
    }
    
    return $warnings;
}

/**
 * Get weather data from OpenWeatherMap
 */
function getWeatherData() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT api_key FROM integration_settings WHERE source = 'pagasa' LIMIT 1");
    $apiKeyRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $apiKey = $apiKeyRow['api_key'] ?? '';
    
    if (empty($apiKey)) {
        return [];
    }
    
    $weatherData = [];
    $locations = [
        ['name' => 'Quezon City', 'lat' => 14.6488, 'lon' => 121.0509],
        ['name' => 'Manila', 'lat' => 14.5995, 'lon' => 120.9842],
        ['name' => 'Makati', 'lat' => 14.5547, 'lon' => 121.0244],
    ];
    
    foreach ($locations as $loc) {
        $url = "https://api.openweathermap.org/data/2.5/weather?lat={$loc['lat']}&lon={$loc['lon']}&appid={$apiKey}&units=metric";
        $response = @file_get_contents($url);
        if ($response) {
            $data = json_decode($response, true);
            if ($data) {
                $weatherData[$loc['name']] = $data;
            }
        }
    }
    
    return $weatherData;
}

/**
 * Send notifications to all subscribed users
 */
function sendNotificationsToSubscribers($alertId, $warning, $settings) {
    global $pdo;
    
    $channels = explode(',', $settings['ai_channels'] ?? 'sms,email');
    $channels = array_map('trim', $channels);
    
    // Get all active subscribers
    $categoryName = mapWarningTypeToCategory($warning['type']);
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.user_id, s.categories, s.channels, s.preferred_language,
               u.name, u.email, u.phone
        FROM subscriptions s
        LEFT JOIN users u ON u.id = s.user_id
        WHERE s.status = 'active'
        AND (s.categories LIKE ? OR s.categories = 'all' OR s.categories LIKE '%weather%' OR s.categories LIKE '%earthquake%' OR s.categories LIKE '%general%')
    ");
    $categoryPattern = "%{$categoryName}%";
    $stmt->execute([$categoryPattern]);
    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subscribers)) {
        return 0;
    }
    
    $translationHelper = new AlertTranslationHelper($pdo);
    $sentCount = 0;
    
    foreach ($subscribers as $subscriber) {
        $userId = $subscriber['user_id'];
        $userChannels = explode(',', $subscriber['channels'] ?? '');
        $userChannels = array_map('trim', $userChannels);
        
        // Get translated alert for user's preferred language
        $userLanguage = $subscriber['preferred_language'] ?? 'en';
        $translatedAlert = $translationHelper->getTranslatedAlert($alertId, $userLanguage, $userId);
        
        if (!$translatedAlert) {
            // Fallback to original warning
            $translatedAlert = [
                'title' => $warning['title'],
                'message' => $warning['content']
            ];
        }
        
        $message = $translatedAlert['title'] . "\n\n" . $translatedAlert['message'];
        
        // Send via each enabled channel
        foreach ($channels as $channel) {
            if (!in_array($channel, $userChannels) && !empty($userChannels)) {
                continue; // User hasn't subscribed to this channel
            }
            
            if ($channel === 'sms' && !empty($subscriber['phone'])) {
                sendSMSNotification($subscriber['phone'], $message, $alertId);
                $sentCount++;
            } elseif ($channel === 'email' && !empty($subscriber['email'])) {
                sendEmailNotification($subscriber['email'], $subscriber['name'], $translatedAlert['title'], $translatedAlert['message'], $alertId);
                $sentCount++;
            } elseif ($channel === 'pa') {
                // PA System notification (log only)
                logPANotification($message, $alertId);
                $sentCount++;
            }
        }
    }
    
    return $sentCount;
}

/**
 * Send SMS notification
 */
function sendSMSNotification($phone, $message, $alertId) {
    global $pdo;
    
    // Log notification
    $stmt = $pdo->prepare("
        INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address)
        VALUES ('sms', ?, ?, ?, 'high', 'pending', NOW(), 'ai_system', '127.0.0.1')
    ");
    $stmt->execute([$message, $phone, $phone]);
    
    // Try to send via SMS helper if available
    if (file_exists(__DIR__ . '/../../USERS/lib/sms.php')) {
        require_once __DIR__ . '/../../USERS/lib/sms.php';
        $smsError = null;
        $smsSent = sendSMS($phone, $message, $smsError);
        
        if ($smsSent) {
            $stmt = $pdo->prepare("UPDATE notification_logs SET status = 'success' WHERE id = ?");
            $stmt->execute([$pdo->lastInsertId()]);
        } else {
            error_log("SMS sending failed for $phone: " . ($smsError ?? 'Unknown error'));
        }
    } else {
        error_log("SMS notification queued for $phone (SMS library not available)");
    }
}

/**
 * Send Email notification
 */
function sendEmailNotification($email, $name, $subject, $body, $alertId) {
    global $pdo;
    
    // Log notification
    $stmt = $pdo->prepare("
        INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address)
        VALUES ('email', ?, ?, ?, 'high', 'pending', NOW(), 'ai_system', '127.0.0.1')
    ");
    $emailMessage = $subject . "\n\n" . $body;
    $stmt->execute([$emailMessage, $email, $email]);
    
    // Try to send email
    $emailSent = false;
    
    // Try PHPMailer if available
    if (file_exists(__DIR__ . '/../../USERS/lib/mail.php')) {
        require_once __DIR__ . '/../../USERS/lib/mail.php';
        $error = null;
        $emailSent = sendSMTPMail($email, $subject, $body, false, $error);
    } else {
        // Fallback to PHP mail()
        $headers = "From: noreply@emergency-com.local\r\n";
        $headers .= "Reply-To: support@emergency-com.local\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $emailSent = @mail($email, $subject, $body, $headers);
    }
    
    if ($emailSent) {
        $stmt = $pdo->prepare("UPDATE notification_logs SET status = 'success' WHERE id = ?");
        $stmt->execute([$pdo->lastInsertId()]);
    } else {
        error_log("Email sending failed for $email");
    }
}

/**
 * Log PA System notification
 */
function logPANotification($message, $alertId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address)
        VALUES ('pa', ?, 'pa_system', 'all', 'high', 'success', NOW(), 'ai_system', '127.0.0.1')
    ");
    $stmt->execute([$message]);
}

/**
 * Map warning type to alert category
 */
function mapWarningTypeToCategory($type) {
    $mapping = [
        'typhoon' => 'Weather',
        'heavy_rain' => 'Weather',
        'flooding' => 'Weather',
        'strong_winds' => 'Weather',
        'thunderstorm' => 'Weather',
        'earthquake' => 'Earthquake',
        'tsunami' => 'Earthquake',
        'landslide' => 'General',
        'fire_incident' => 'Fire',
        'ash_fall' => 'General'
    ];
    
    return $mapping[$type] ?? 'General';
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

