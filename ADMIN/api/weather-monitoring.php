<?php
/**
 * Weather Monitoring API
 * Fetches weather data from OpenWeather API (using PAGASA API key)
 * Returns weather data for Philippines locations
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once 'config.env.php';

$action = $_GET['action'] ?? 'current';

function isPlaceholderWeatherKey($key) {
    $key = trim((string)$key);
    if ($key === '') {
        return true;
    }

    // Legacy/demo placeholders that should never be used in runtime.
    $invalid = [
        'f35609a701ba47952fba4fd4604c12c7',
        'YOUR_OPENWEATHER_API_KEY',
    ];

    return in_array($key, $invalid, true);
}

function getConfiguredWeatherKey() {
    if (!function_exists('getSecureConfig')) {
        return null;
    }

    $candidates = [
        getSecureConfig('OPENWEATHER_API_KEY', ''),
        getSecureConfig('OPEN_WEATHER_API_KEY', ''),
        getSecureConfig('OWM_API_KEY', ''),
        getSecureConfig('PAGASA_API_KEY', ''),
        getSecureConfig('PAGASA_OPENWEATHER_API_KEY', ''),
        getSecureConfig('WEATHER_API_KEY', ''),
    ];

    foreach ($candidates as $candidate) {
        $candidate = trim((string)$candidate);
        if (!isPlaceholderWeatherKey($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function getDbWeatherKey($pdo) {
    if ($pdo === null) {
        return null;
    }
    ensureIntegrationSettingsTable($pdo);
    try {
        $stmt = $pdo->prepare("SELECT api_key FROM integration_settings WHERE source = 'pagasa' LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch();
        $dbKey = trim((string)($result['api_key'] ?? ''));
        if (!isPlaceholderWeatherKey($dbKey)) {
            return $dbKey;
        }
    } catch (PDOException $e) {
        error_log("Get PAGASA/OpenWeather key error: " . $e->getMessage());
    }
    return null;
}

function ensureIntegrationSettingsTable($pdo) {
    if ($pdo === null) {
        return false;
    }
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS integration_settings (
                source VARCHAR(64) NOT NULL PRIMARY KEY,
                enabled TINYINT(1) NOT NULL DEFAULT 0,
                api_key VARCHAR(255) DEFAULT NULL,
                api_url VARCHAR(255) DEFAULT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        return true;
    } catch (PDOException $e) {
        $message = $e->getMessage();
        error_log('Ensure integration_settings table error: ' . $message);

        // Handle corrupted InnoDB metadata/table (common local issue: "doesn't exist in engine")
        if (stripos($message, "doesn't exist in engine") !== false || stripos($message, 'error code: 1932') !== false) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS integration_settings");
                $pdo->exec("
                    CREATE TABLE integration_settings (
                        source VARCHAR(64) NOT NULL PRIMARY KEY,
                        enabled TINYINT(1) NOT NULL DEFAULT 0,
                        api_key VARCHAR(255) DEFAULT NULL,
                        api_url VARCHAR(255) DEFAULT NULL,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                error_log('Recreated corrupted integration_settings table');
                return true;
            } catch (PDOException $recreateError) {
                error_log('Failed to recreate integration_settings table: ' . $recreateError->getMessage());
            }
        }
        return false;
    }
}

function persistWeatherKeyToDb($pdo, $apiKey) {
    if ($pdo === null || isPlaceholderWeatherKey($apiKey)) {
        return;
    }
    ensureIntegrationSettingsTable($pdo);
    try {
        $stmt = $pdo->prepare("
            INSERT INTO integration_settings (source, enabled, api_key, api_url, updated_at)
            VALUES ('pagasa', 1, ?, 'https://api.openweathermap.org/data/2.5/', NOW())
            ON DUPLICATE KEY UPDATE
                api_key = VALUES(api_key),
                api_url = VALUES(api_url),
                updated_at = NOW()
        ");
        $stmt->execute([$apiKey]);
    } catch (PDOException $e) {
        error_log("Persist PAGASA/OpenWeather key error: " . $e->getMessage());
    }
}

// Resolve OpenWeather API key:
// 1) secure config/env, 2) DB integration_settings (pagasa), 3) none.
$apiKey = getConfiguredWeatherKey();
if ($apiKey === null) {
    $apiKey = getDbWeatherKey($pdo);
} else {
    // Keep DB in sync when local config/env key exists (helpful for modules reading DB directly).
    persistWeatherKeyToDb($pdo, $apiKey);
}

// Philippines coordinates (center)
$philippinesLat = 12.8797;
$philippinesLon = 121.7740;

// Major cities in Philippines
$philippinesCities = [
    ['name' => 'Manila', 'lat' => 14.5995, 'lon' => 120.9842],
    ['name' => 'Cebu City', 'lat' => 10.3157, 'lon' => 123.8854],
    ['name' => 'Davao City', 'lat' => 7.1907, 'lon' => 125.4553],
    ['name' => 'Quezon City', 'lat' => 14.6760, 'lon' => 121.0437],
    ['name' => 'Makati', 'lat' => 14.5547, 'lon' => 121.0244],
    ['name' => 'Baguio', 'lat' => 16.4023, 'lon' => 120.5960],
    ['name' => 'Iloilo City', 'lat' => 10.7202, 'lon' => 122.5621],
    ['name' => 'Cagayan de Oro', 'lat' => 8.4542, 'lon' => 124.6319],
    ['name' => 'Bacolod', 'lat' => 10.6407, 'lon' => 122.9689],
    ['name' => 'Zamboanga City', 'lat' => 6.9214, 'lon' => 122.0790]
];

function fetchWeatherData($lat, $lon, $apiKey) {
    if (isPlaceholderWeatherKey($apiKey)) {
        return ['error' => 'OpenWeather API key is not configured. Set OPENWEATHER_API_KEY in ADMIN/api/config.local.php (or .env), then reload.'];
    }

    $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error];
    }
    
    if ($httpCode !== 200) {
        return ['error' => "HTTP {$httpCode}: " . $response];
    }
    
    return json_decode($response, true);
}

function fetchForecastData($lat, $lon, $apiKey) {
    if (isPlaceholderWeatherKey($apiKey)) {
        return ['error' => 'OpenWeather API key is not configured. Set OPENWEATHER_API_KEY in ADMIN/api/config.local.php (or .env), then reload.'];
    }

    $url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error];
    }
    
    if ($httpCode !== 200) {
        return ['error' => "HTTP {$httpCode}: " . $response];
    }
    
    return json_decode($response, true);
}

function getRainPreparation($rain, $condition) {
    if ($rain > 5 || $condition === 'Thunderstorm') {
        return [
            'title' => 'Heavy Rain Expected',
            'actions' => [
                'Stay indoors if possible',
                'Avoid low-lying and flood-prone areas',
                'Keep emergency supplies ready',
                'Monitor weather updates',
                'Postpone outdoor activities'
            ],
            'priority' => 'high'
        ];
    } elseif ($rain > 2) {
        return [
            'title' => 'Moderate Rain Expected',
            'actions' => [
                'Carry an umbrella or raincoat',
                'Drive carefully - roads may be slippery',
                'Avoid unnecessary travel',
                'Keep electronic devices protected'
            ],
            'priority' => 'medium'
        ];
    } else {
        return [
            'title' => 'Light Rain Expected',
            'actions' => [
                'Carry an umbrella',
                'Wear appropriate clothing',
                'Be cautious on wet surfaces'
            ],
            'priority' => 'low'
        ];
    }
}

function getHotWeatherPreparation($temp, $feelsLike, $humidity) {
    if ($temp >= 35 || $feelsLike >= 38) {
        return [
            'title' => 'Extreme Heat Warning',
            'actions' => [
                'Stay hydrated - drink plenty of water',
                'Avoid direct sunlight during peak hours (10 AM - 4 PM)',
                'Wear light-colored, loose-fitting clothing',
                'Use sunscreen (SPF 30+)',
                'Take breaks in shaded or air-conditioned areas',
                'Check on elderly and children',
                'Never leave children or pets in vehicles'
            ],
            'priority' => 'high'
        ];
    } elseif ($temp >= 33) {
        return [
            'title' => 'Very Hot Weather',
            'actions' => [
                'Drink water regularly',
                'Limit outdoor activities',
                'Wear a hat and use sunscreen',
                'Seek shade when possible'
            ],
            'priority' => 'medium'
        ];
    } else {
        return [
            'title' => 'Hot Weather',
            'actions' => [
                'Stay hydrated',
                'Wear light clothing',
                'Use sunscreen if outdoors'
            ],
            'priority' => 'low'
        ];
    }
}

if ($action === 'current') {
    // Get weather for a specific location or default to Manila
    $lat = isset($_GET['lat']) ? floatval($_GET['lat']) : $philippinesCities[0]['lat'];
    $lon = isset($_GET['lon']) ? floatval($_GET['lon']) : $philippinesCities[0]['lon'];
    
    $weatherData = fetchWeatherData($lat, $lon, $apiKey);
    
    if (isset($weatherData['error'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch weather data: ' . $weatherData['error']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => $weatherData
        ]);
    }
} elseif ($action === 'multiple') {
    // Get weather for multiple cities
    $cities = isset($_GET['cities']) ? json_decode($_GET['cities'], true) : $philippinesCities;
    
    $results = [];
    foreach ($cities as $city) {
        $weatherData = fetchWeatherData($city['lat'], $city['lon'], $apiKey);
        if (!isset($weatherData['error'])) {
            $results[] = [
                'name' => $city['name'],
                'lat' => $city['lat'],
                'lon' => $city['lon'],
                'weather' => $weatherData
            ];
        }
        // Small delay to avoid rate limiting
        usleep(100000); // 0.1 second
    }
    
    echo json_encode([
        'success' => true,
        'data' => $results
    ]);
} elseif ($action === 'map') {
    // Check if API key is available
    if (!$apiKey) {
        echo json_encode([
            'success' => false,
            'message' => 'OpenWeather/PAGASA API key not configured. Set OPENWEATHER_API_KEY in ADMIN/api/config.local.php (or .env).',
            'data' => []
        ]);
        exit();
    }
    
    // Get weather data for map display (multiple points across Philippines)
    $mapPoints = [];
    
    // Sample points across Philippines for map visualization - Quezon City first
    $samplePoints = [
        ['lat' => 14.6760, 'lon' => 121.0437, 'name' => 'Quezon City'],
        ['lat' => 14.5995, 'lon' => 120.9842, 'name' => 'Manila'],
        ['lat' => 14.5547, 'lon' => 121.0244, 'name' => 'Makati'],
        ['lat' => 10.3157, 'lon' => 123.8854, 'name' => 'Cebu'],
        ['lat' => 7.1907, 'lon' => 125.4553, 'name' => 'Davao'],
        ['lat' => 16.4023, 'lon' => 120.5960, 'name' => 'Baguio'],
        ['lat' => 8.4542, 'lon' => 124.6319, 'name' => 'Cagayan de Oro'],
        ['lat' => 6.9214, 'lon' => 122.0790, 'name' => 'Zamboanga']
    ];
    
    foreach ($samplePoints as $point) {
        $weatherData = fetchWeatherData($point['lat'], $point['lon'], $apiKey);
        if (!isset($weatherData['error'])) {
            $mapPoints[] = [
                'lat' => $point['lat'],
                'lon' => $point['lon'],
                'name' => $point['name'],
                'temp' => $weatherData['main']['temp'] ?? null,
                'condition' => $weatherData['weather'][0]['main'] ?? null,
                'icon' => $weatherData['weather'][0]['icon'] ?? null,
                'humidity' => $weatherData['main']['humidity'] ?? null,
                'windSpeed' => $weatherData['wind']['speed'] ?? null,
                'windDeg' => $weatherData['wind']['deg'] ?? null,
                'windGust' => $weatherData['wind']['gust'] ?? null
            ];
        }
        usleep(100000); // 0.1 second delay
    }
    
    echo json_encode([
        'success' => true,
        'data' => $mapPoints,
        'center' => [
            'lat' => 14.6760, // Quezon City
            'lon' => 121.0437
        ]
    ]);
} elseif ($action === 'forecast') {
    // Check if API key is available
    if (!$apiKey) {
        echo json_encode([
            'success' => false,
            'message' => 'OpenWeather/PAGASA API key not configured. Set OPENWEATHER_API_KEY in ADMIN/api/config.local.php (or .env).'
        ]);
        exit();
    }
    
    // Get weather forecast for a specific location or default to Quezon City
    $lat = isset($_GET['lat']) ? floatval($_GET['lat']) : 14.6760; // Quezon City
    $lon = isset($_GET['lon']) ? floatval($_GET['lon']) : 121.0437;
    
    $forecastData = fetchForecastData($lat, $lon, $apiKey);
    
    if (isset($forecastData['error'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch forecast data: ' . $forecastData['error']
        ]);
    } else {
        // Process forecast data to identify rain and hot weather predictions
        $predictions = [];
        $rainPredictions = [];
        $hotWeatherPredictions = [];
        
        if (isset($forecastData['list']) && is_array($forecastData['list'])) {
            foreach ($forecastData['list'] as $forecast) {
                $timestamp = $forecast['dt'];
                $dateTime = new DateTime('@' . $timestamp);
                $dateTime->setTimezone(new DateTimeZone('Asia/Manila')); // PHT timezone
                
                $temp = $forecast['main']['temp'];
                $condition = $forecast['weather'][0]['main'] ?? 'Clear';
                $description = strtolower($forecast['weather'][0]['description'] ?? '');
                $rain = $forecast['rain']['3h'] ?? 0; // Rain volume for next 3 hours (mm)
                $humidity = $forecast['main']['humidity'];
                $feelsLike = $forecast['main']['feels_like'];
                
                $prediction = [
                    'datetime' => $dateTime->format('Y-m-d H:i:s'),
                    'date' => $dateTime->format('M d, Y'),
                    'time' => $dateTime->format('h:i A'),
                    'day' => $dateTime->format('l'),
                    'timestamp' => $timestamp,
                    'temp' => round($temp, 1),
                    'feels_like' => round($feelsLike, 1),
                    'condition' => $condition,
                    'description' => $forecast['weather'][0]['description'] ?? '',
                    'icon' => $forecast['weather'][0]['icon'] ?? '01d',
                    'rain' => round($rain, 2),
                    'pop' => isset($forecast['pop']) ? (int)round(floatval($forecast['pop']) * 100) : 0,
                    'humidity' => $humidity,
                    'wind_speed' => $forecast['wind']['speed'] ?? 0,
                    'wind_deg' => $forecast['wind']['deg'] ?? 0
                ];
                
                $predictions[] = $prediction;
                
                // Identify rain predictions
                if ($rain > 0 || in_array($condition, ['Rain', 'Drizzle', 'Thunderstorm']) || 
                    strpos($description, 'rain') !== false || strpos($description, 'drizzle') !== false) {
                    $rainPredictions[] = [
                        'prediction' => $prediction,
                        'severity' => $rain > 5 ? 'heavy' : ($rain > 2 ? 'moderate' : 'light'),
                        'preparation' => getRainPreparation($rain, $condition)
                    ];
                }
                
                // Identify hot weather predictions
                if ($temp >= 32 || $feelsLike >= 35) {
                    $hotWeatherPredictions[] = [
                        'prediction' => $prediction,
                        'severity' => $temp >= 35 ? 'extreme' : ($temp >= 33 ? 'very_hot' : 'hot'),
                        'preparation' => getHotWeatherPreparation($temp, $feelsLike, $humidity)
                    ];
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'location' => $forecastData['city']['name'] ?? 'Quezon City',
            'country' => $forecastData['city']['country'] ?? 'PH',
            'forecast' => $predictions,
            'rain_predictions' => $rainPredictions,
            'hot_weather_predictions' => $hotWeatherPredictions,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
} elseif ($action === 'getApiKey') {
    // Return OpenWeatherMap API key for layer tiles
    if (!isPlaceholderWeatherKey($apiKey)) {
        echo json_encode([
            'success' => true,
            'apiKey' => $apiKey
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'OpenWeather/PAGASA API key not configured. Set OPENWEATHER_API_KEY in ADMIN/api/config.local.php (or .env).',
            'apiKey' => null
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>

