<?php
/**
 * Weather Monitoring API
 * Fetches weather data from OpenWeather API (using PAGASA API key)
 * Returns weather data for Philippines locations
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once 'config.env.php';
if (file_exists(__DIR__ . '/secure-api-config.php')) {
    require_once __DIR__ . '/secure-api-config.php';
}

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
    // Prefer centralized secure resolver when available.
    if (function_exists('getOpenWeatherApiKey')) {
        try {
            $key = trim((string)getOpenWeatherApiKey(true));
            if (!isPlaceholderWeatherKey($key)) {
                return $key;
            }
        } catch (Throwable $e) {
            error_log('getOpenWeatherApiKey failed in weather-monitoring.php: ' . $e->getMessage());
        }
    }

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
        $sources = ['pagasa', 'openweather', 'open_weather', 'weather'];
        foreach ($sources as $source) {
            $stmt = $pdo->prepare("SELECT api_key FROM integration_settings WHERE source = ? LIMIT 1");
            $stmt->execute([$source]);
            $result = $stmt->fetch();
            $dbKey = trim((string)($result['api_key'] ?? ''));
            if (!isPlaceholderWeatherKey($dbKey)) {
                return $dbKey;
            }
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

function weatherHttpJsonGet($url, $timeoutSeconds = 10) {
    $response = null;
    $httpCode = 0;
    $error = '';

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = (string)curl_error($ch);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => ['timeout' => $timeoutSeconds]
        ]);
        $response = @file_get_contents($url, false, $context);
        if (isset($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d+)#', $http_response_header[0], $matches)) {
            $httpCode = (int)$matches[1];
        }
    }

    if ($response === false || $response === null || $response === '') {
        return ['success' => false, 'error' => $error !== '' ? $error : 'No response from weather provider'];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['success' => false, 'error' => 'Invalid JSON response from weather provider'];
    }

    if ($httpCode >= 400) {
        return ['success' => false, 'error' => "HTTP {$httpCode}: {$response}"];
    }

    return ['success' => true, 'data' => $decoded];
}

function weatherCodeToOpenWeatherMeta($code, $isDay = 1) {
    $code = (int)$code;
    $isDayTime = ((int)$isDay) === 1;
    $iconDay = $isDayTime ? 'd' : 'n';

    if ($code === 0) {
        return ['main' => 'Clear', 'description' => 'clear sky', 'icon' => '01' . $iconDay];
    }
    if ($code === 1) {
        return ['main' => 'Clouds', 'description' => 'mainly clear', 'icon' => '02' . $iconDay];
    }
    if ($code === 2) {
        return ['main' => 'Clouds', 'description' => 'partly cloudy', 'icon' => '03' . $iconDay];
    }
    if ($code === 3) {
        return ['main' => 'Clouds', 'description' => 'overcast clouds', 'icon' => '04' . $iconDay];
    }
    if (in_array($code, [45, 48], true)) {
        return ['main' => 'Mist', 'description' => 'fog', 'icon' => '50' . $iconDay];
    }
    if (in_array($code, [51, 53, 55, 56, 57], true)) {
        return ['main' => 'Drizzle', 'description' => 'drizzle', 'icon' => '09' . $iconDay];
    }
    if (in_array($code, [61, 63, 65, 66, 67, 80, 81, 82], true)) {
        return ['main' => 'Rain', 'description' => 'rain', 'icon' => '10' . $iconDay];
    }
    if (in_array($code, [71, 73, 75, 77, 85, 86], true)) {
        return ['main' => 'Snow', 'description' => 'snow', 'icon' => '13' . $iconDay];
    }
    if (in_array($code, [95, 96, 99], true)) {
        return ['main' => 'Thunderstorm', 'description' => 'thunderstorm', 'icon' => '11' . $iconDay];
    }

    return ['main' => 'Clouds', 'description' => 'cloudy', 'icon' => '03' . $iconDay];
}

function resolveWeatherLocationName($lat, $lon) {
    global $philippinesCities;

    $bestName = 'Quezon City';
    $bestDistance = PHP_FLOAT_MAX;
    foreach ($philippinesCities as $city) {
        $distance = (($lat - $city['lat']) ** 2) + (($lon - $city['lon']) ** 2);
        if ($distance < $bestDistance) {
            $bestDistance = $distance;
            $bestName = $city['name'];
        }
    }
    return $bestName;
}

function fetchOpenMeteoCurrentData($lat, $lon) {
    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}"
        . "&current=temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weather_code,wind_speed_10m,wind_direction_10m,wind_gusts_10m,is_day"
        . "&timezone=Asia%2FManila";

    $result = weatherHttpJsonGet($url, 12);
    if (empty($result['success'])) {
        return ['error' => $result['error'] ?? 'Open-Meteo current weather request failed'];
    }

    $current = $result['data']['current'] ?? null;
    if (!is_array($current)) {
        return ['error' => 'Open-Meteo current weather payload is missing current data'];
    }

    $code = (int)($current['weather_code'] ?? 0);
    $meta = weatherCodeToOpenWeatherMeta($code, (int)($current['is_day'] ?? 1));
    $timestamp = isset($current['time']) ? strtotime((string)$current['time']) : time();
    if ($timestamp === false) {
        $timestamp = time();
    }

    $locationName = resolveWeatherLocationName($lat, $lon);

    return [
        'coord' => ['lon' => (float)$lon, 'lat' => (float)$lat],
        'weather' => [[
            'id' => $code,
            'main' => $meta['main'],
            'description' => $meta['description'],
            'icon' => $meta['icon']
        ]],
        'base' => 'open-meteo',
        'main' => [
            'temp' => round((float)($current['temperature_2m'] ?? 0), 1),
            'feels_like' => round((float)($current['apparent_temperature'] ?? ($current['temperature_2m'] ?? 0)), 1),
            'temp_min' => round((float)($current['temperature_2m'] ?? 0), 1),
            'temp_max' => round((float)($current['temperature_2m'] ?? 0), 1),
            'pressure' => null,
            'humidity' => (int)round((float)($current['relative_humidity_2m'] ?? 0))
        ],
        'wind' => [
            'speed' => round(((float)($current['wind_speed_10m'] ?? 0)) / 3.6, 2), // OpenWeather format (m/s)
            'deg' => (int)round((float)($current['wind_direction_10m'] ?? 0)),
            'gust' => isset($current['wind_gusts_10m']) ? round(((float)$current['wind_gusts_10m']) / 3.6, 2) : null
        ],
        'rain' => [
            '1h' => round((float)($current['precipitation'] ?? 0), 2)
        ],
        'clouds' => ['all' => null],
        'dt' => $timestamp,
        'sys' => ['country' => 'PH'],
        'name' => $locationName
    ];
}

function fetchOpenMeteoForecastData($lat, $lon) {
    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}"
        . "&hourly=temperature_2m,relative_humidity_2m,apparent_temperature,precipitation_probability,precipitation,weather_code,wind_speed_10m,wind_direction_10m,is_day"
        . "&forecast_days=7&timezone=Asia%2FManila";

    $result = weatherHttpJsonGet($url, 12);
    if (empty($result['success'])) {
        return ['error' => $result['error'] ?? 'Open-Meteo forecast request failed'];
    }

    $hourly = $result['data']['hourly'] ?? null;
    if (!is_array($hourly) || empty($hourly['time']) || !is_array($hourly['time'])) {
        return ['error' => 'Open-Meteo forecast payload is missing hourly time data'];
    }

    $times = $hourly['time'];
    $list = [];
    $count = count($times);

    // Keep 3-hour cadence to match OpenWeather forecast structure.
    for ($i = 0; $i < $count; $i += 3) {
        $timestamp = strtotime((string)$times[$i]);
        if ($timestamp === false) {
            continue;
        }

        $code = (int)($hourly['weather_code'][$i] ?? 0);
        $meta = weatherCodeToOpenWeatherMeta($code, (int)($hourly['is_day'][$i] ?? 1));

        $list[] = [
            'dt' => $timestamp,
            'main' => [
                'temp' => round((float)($hourly['temperature_2m'][$i] ?? 0), 1),
                'feels_like' => round((float)($hourly['apparent_temperature'][$i] ?? ($hourly['temperature_2m'][$i] ?? 0)), 1),
                'humidity' => (int)round((float)($hourly['relative_humidity_2m'][$i] ?? 0))
            ],
            'weather' => [[
                'id' => $code,
                'main' => $meta['main'],
                'description' => $meta['description'],
                'icon' => $meta['icon']
            ]],
            'wind' => [
                'speed' => round(((float)($hourly['wind_speed_10m'][$i] ?? 0)) / 3.6, 2), // OpenWeather format (m/s)
                'deg' => (int)round((float)($hourly['wind_direction_10m'][$i] ?? 0))
            ],
            'rain' => [
                '3h' => round((float)($hourly['precipitation'][$i] ?? 0), 2)
            ],
            'pop' => max(0, min(1, ((float)($hourly['precipitation_probability'][$i] ?? 0)) / 100)),
            'dt_txt' => date('Y-m-d H:i:s', $timestamp)
        ];
    }

    $locationName = resolveWeatherLocationName($lat, $lon);

    return [
        'cod' => '200',
        'list' => $list,
        'city' => [
            'name' => $locationName,
            'country' => 'PH',
            'coord' => ['lat' => (float)$lat, 'lon' => (float)$lon],
            'timezone' => 8 * 3600
        ]
    ];
}

function fetchWeatherData($lat, $lon, $apiKey) {
    if (!isPlaceholderWeatherKey($apiKey)) {
        $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";
        $result = weatherHttpJsonGet($url, 10);
        if (!empty($result['success']) && is_array($result['data'])) {
            return $result['data'];
        }
    }

    // No key or OpenWeather failed: use a no-key fallback provider.
    return fetchOpenMeteoCurrentData($lat, $lon);
}

function fetchForecastData($lat, $lon, $apiKey) {
    if (!isPlaceholderWeatherKey($apiKey)) {
        $url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";
        $result = weatherHttpJsonGet($url, 10);
        if (!empty($result['success']) && is_array($result['data'])) {
            return $result['data'];
        }
    }

    // No key or OpenWeather failed: use a no-key fallback provider.
    return fetchOpenMeteoForecastData($lat, $lon);
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

