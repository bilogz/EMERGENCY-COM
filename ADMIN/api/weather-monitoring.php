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
if (PHP_SAPI === 'cli') {
    foreach ($argv ?? [] as $arg) {
        if (strpos((string)$arg, '--action=') === 0) {
            $action = substr((string)$arg, 9);
        }
    }
}

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

function buildOpenMeteoUrl($lat, $lon, array $extraParams = []) {
    $configured = function_exists('getSecureConfig') ? (string)getSecureConfig('OPEN_METEO_URL', '') : '';
    $base = 'https://api.open-meteo.com/v1/forecast';
    $params = [];
    if ($configured !== '') {
        $parts = parse_url($configured);
        if (!empty($parts['scheme']) && !empty($parts['host'])) {
            $base = $parts['scheme'] . '://' . $parts['host'] . ($parts['path'] ?? '/v1/forecast');
        }
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $params);
        }
    }
    $requiredHourly = [
        'temperature_2m', 'visibility', 'apparent_temperature', 'relative_humidity_2m',
        'precipitation_probability', 'precipitation', 'showers', 'rain', 'weather_code',
        'wind_speed_10m', 'wind_gusts_10m', 'wind_direction_10m', 'is_day'
    ];
    $existingHourly = array_filter(array_map('trim', explode(',', (string)($params['hourly'] ?? ''))));
    $params['hourly'] = implode(',', array_values(array_unique(array_merge($existingHourly, $requiredHourly))));
    $params['latitude'] = $lat;
    $params['longitude'] = $lon;
    $params['timezone'] = 'Asia/Manila';
    unset($params['utm_source']);
    foreach ($extraParams as $key => $value) {
        $params[$key] = $value;
    }
    return $base . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function fetchOpenMeteoForecastData($lat, $lon) {
    $url = buildOpenMeteoUrl($lat, $lon, ['forecast_days' => 7]);

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
                'deg' => (int)round((float)($hourly['wind_direction_10m'][$i] ?? 0)),
                'gust' => isset($hourly['wind_gusts_10m'][$i]) ? round(((float)$hourly['wind_gusts_10m'][$i]) / 3.6, 2) : null
            ],
            'rain' => [
                '3h' => round((float)($hourly['precipitation'][$i] ?? 0), 2)
            ],
            'pop' => max(0, min(1, ((float)($hourly['precipitation_probability'][$i] ?? 0)) / 100)),
            'open_meteo' => [
                'precipitation_mm' => round((float)($hourly['precipitation'][$i] ?? 0), 2),
                'rain_mm' => round((float)($hourly['rain'][$i] ?? 0), 2),
                'showers_mm' => round((float)($hourly['showers'][$i] ?? 0), 2),
                'precipitation_probability' => (int)round((float)($hourly['precipitation_probability'][$i] ?? 0)),
                'visibility_m' => isset($hourly['visibility'][$i]) ? (int)round((float)$hourly['visibility'][$i]) : null,
                'wind_speed_kmh' => round((float)($hourly['wind_speed_10m'][$i] ?? 0), 1),
                'wind_gust_kmh' => isset($hourly['wind_gusts_10m'][$i]) ? round((float)$hourly['wind_gusts_10m'][$i], 1) : null,
                'wind_direction_deg' => (int)round((float)($hourly['wind_direction_10m'][$i] ?? 0)),
                'weather_code' => $code
            ],
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

function weatherRiskLevelRank($level) {
    $map = ['normal' => 0, 'watch' => 1, 'advisory' => 2, 'warning' => 3];
    return $map[strtolower((string)$level)] ?? 0;
}

function weatherRiskLabel($level) {
    $labels = ['normal' => 'Normal', 'watch' => 'Watch', 'advisory' => 'Advisory', 'warning' => 'Warning'];
    return $labels[strtolower((string)$level)] ?? 'Normal';
}

function weatherRiskSeverity($level) {
    $map = ['normal' => 'low', 'watch' => 'medium', 'advisory' => 'high', 'warning' => 'critical'];
    return $map[strtolower((string)$level)] ?? 'low';
}

function getForecastMetric(array $forecast, string $key, $fallback = 0) {
    if (isset($forecast['open_meteo'][$key])) return $forecast['open_meteo'][$key];
    if ($key === 'precipitation_mm') return $forecast['rain']['3h'] ?? $fallback;
    if ($key === 'precipitation_probability') return isset($forecast['pop']) ? (int)round(((float)$forecast['pop']) * 100) : $fallback;
    if ($key === 'wind_speed_kmh') return isset($forecast['wind']['speed']) ? round(((float)$forecast['wind']['speed']) * 3.6, 1) : $fallback;
    if ($key === 'wind_gust_kmh') return isset($forecast['wind']['gust']) ? round(((float)$forecast['wind']['gust']) * 3.6, 1) : $fallback;
    if ($key === 'weather_code') return $forecast['weather'][0]['id'] ?? $fallback;
    return $fallback;
}

function analyzeWeatherRisks(array $forecastData) {
    $items = array_values(array_filter($forecastData['list'] ?? [], 'is_array'));
    $window = array_slice($items, 0, 16);
    $next24 = array_slice($items, 0, 8);
    $startedAt = $window[0]['dt_txt'] ?? date('Y-m-d H:i:s');
    $endedAt = end($window)['dt_txt'] ?? $startedAt;

    $maxRain = 0.0; $rain24 = 0.0; $rain48 = 0.0; $maxPop = 0; $minVisibility = null;
    $maxWind = 0.0; $maxGust = 0.0; $maxTemp = 0.0; $minTemp = null; $maxFeels = 0.0; $maxHumidity = 0;
    $hasThunderCode = false;
    $peakRainPeriod = null; $peakRainScore = -1;

    foreach ($window as $idx => $forecast) {
        $precip = (float)getForecastMetric($forecast, 'precipitation_mm', 0);
        $rain = max($precip, (float)getForecastMetric($forecast, 'rain_mm', 0), (float)getForecastMetric($forecast, 'showers_mm', 0));
        $pop = (int)getForecastMetric($forecast, 'precipitation_probability', 0);
        $visibility = getForecastMetric($forecast, 'visibility_m', null);
        $wind = (float)getForecastMetric($forecast, 'wind_speed_kmh', 0);
        $gust = (float)getForecastMetric($forecast, 'wind_gust_kmh', 0);
        $code = (int)getForecastMetric($forecast, 'weather_code', 0);
        $temp = (float)($forecast['main']['temp'] ?? 0);
        $feels = (float)($forecast['main']['feels_like'] ?? $temp);
        $humidity = (int)($forecast['main']['humidity'] ?? 0);

        $maxRain = max($maxRain, $rain);
        $rain48 += $rain;
        if ($idx < count($next24)) $rain24 += $rain;
        $maxPop = max($maxPop, $pop);
        if ($visibility !== null) $minVisibility = $minVisibility === null ? (int)$visibility : min($minVisibility, (int)$visibility);
        $maxWind = max($maxWind, $wind);
        $maxGust = max($maxGust, $gust);
        $maxTemp = max($maxTemp, $temp);
        $minTemp = $minTemp === null ? $temp : min($minTemp, $temp);
        $rainScore = ($rain * 10) + $pop;
        if ($rainScore > $peakRainScore) {
            $peakRainScore = $rainScore;
            $peakRainPeriod = $forecast['dt_txt'] ?? null;
        }
        $maxFeels = max($maxFeels, $feels);
        $maxHumidity = max($maxHumidity, $humidity);
        if (in_array($code, [95, 96, 99], true)) $hasThunderCode = true;
    }

    $rainLevel = 'normal';
    if ($maxRain >= 20 || ($maxPop >= 90 && $maxRain >= 10)) $rainLevel = 'warning';
    elseif ($maxRain >= 10 || ($maxPop >= 80 && $maxRain >= 5)) $rainLevel = 'advisory';
    elseif ($maxRain >= 5 || $maxPop >= 70) $rainLevel = 'watch';

    $floodLevel = 'normal';
    if ($rain24 >= 50 || $rain48 >= 80 || ($maxRain >= 20 && $maxPop >= 80)) $floodLevel = 'warning';
    elseif ($rain24 >= 30 || $rain48 >= 50 || ($maxRain >= 10 && $maxPop >= 75)) $floodLevel = 'advisory';
    elseif ($rain24 >= 15 || $rain48 >= 25 || $maxPop >= 70) $floodLevel = 'watch';

    $windLevel = 'normal';
    if ($maxGust >= 75 || $maxWind >= 55) $windLevel = 'warning';
    elseif ($maxGust >= 55 || $maxWind >= 40) $windLevel = 'advisory';
    elseif ($maxGust >= 40 || $maxWind >= 30) $windLevel = 'watch';

    $visibilityLevel = 'normal';
    if ($minVisibility !== null && $minVisibility <= 1000) $visibilityLevel = 'warning';
    elseif ($minVisibility !== null && $minVisibility <= 3000) $visibilityLevel = 'advisory';
    elseif ($minVisibility !== null && $minVisibility <= 5000) $visibilityLevel = 'watch';

    $stormLevel = 'normal';
    if ($hasThunderCode && ($maxGust >= 55 || $maxRain >= 10)) $stormLevel = 'warning';
    elseif ($hasThunderCode) $stormLevel = 'advisory';
    elseif ($maxRain >= 10 && $maxGust >= 40) $stormLevel = 'watch';

    $heatLevel = 'normal';
    if ($maxFeels >= 42 || $maxTemp >= 38) $heatLevel = 'warning';
    elseif ($maxFeels >= 38 || $maxTemp >= 35) $heatLevel = 'advisory';
    elseif ($maxFeels >= 35 || ($maxTemp >= 33 && $maxHumidity >= 70)) $heatLevel = 'watch';

    $risks = [
        ['key' => 'rainfall', 'label' => 'Rainfall', 'level' => $rainLevel, 'summary' => sprintf('Max forecast rain %.1f mm/3h, probability up to %d%%.', $maxRain, $maxPop)],
        ['key' => 'flood_risk', 'label' => 'Flood Risk Watch', 'level' => $floodLevel, 'summary' => sprintf('Forecast-based only: %.1f mm in 24h, %.1f mm in 48h. This does not confirm flooding is occurring.', $rain24, $rain48), 'more_info_url' => 'https://pagasa.dost.gov.ph/flood#flood-information'],
        ['key' => 'wind', 'label' => 'Wind', 'level' => $windLevel, 'summary' => sprintf('Wind up to %.1f km/h, gusts up to %.1f km/h.', $maxWind, $maxGust)],
        ['key' => 'visibility', 'label' => 'Visibility', 'level' => $visibilityLevel, 'summary' => $minVisibility === null ? 'Visibility data is not available.' : sprintf('Lowest forecast visibility: %.1f km.', $minVisibility / 1000)],
        ['key' => 'thunderstorm', 'label' => 'Thunderstorm', 'level' => $stormLevel, 'summary' => $hasThunderCode ? 'Thunderstorm weather code appears in the forecast window.' : 'No thunderstorm weather code in the forecast window.'],
        ['key' => 'heat', 'label' => 'Heat', 'level' => $heatLevel, 'summary' => sprintf('Temperature up to %.1f C, feels-like up to %.1f C, humidity up to %d%%.', $maxTemp, $maxFeels, $maxHumidity)]
    ];

    usort($risks, function ($a, $b) { return weatherRiskLevelRank($b['level']) <=> weatherRiskLevelRank($a['level']); });
    $top = $risks[0] ?? ['key' => 'normal', 'label' => 'Weather', 'level' => 'normal'];

    return [
        'location' => $forecastData['city']['name'] ?? 'Quezon City',
        'source' => 'Open-Meteo forecast',
        'generated_at' => date('Y-m-d H:i:s'),
        'forecast_window' => ['start' => $startedAt, 'end' => $endedAt],
        'overall' => ['key' => $top['key'], 'label' => $top['label'], 'level' => $top['level'], 'severity' => weatherRiskSeverity($top['level'])],
        'metrics' => [
            'max_rain_3h_mm' => round($maxRain, 1), 'rain_24h_mm' => round($rain24, 1), 'rain_48h_mm' => round($rain48, 1),
            'max_precipitation_probability' => $maxPop, 'min_visibility_m' => $minVisibility, 'max_wind_kmh' => round($maxWind, 1),
            'max_gust_kmh' => round($maxGust, 1), 'min_temp_c' => $minTemp === null ? null : round($minTemp, 1), 'max_temp_c' => round($maxTemp, 1), 'max_feels_like_c' => round($maxFeels, 1), 'max_humidity' => $maxHumidity, 'peak_period' => $peakRainPeriod
        ],
        'risks' => $risks
    ];
}

function ensureWeatherRiskAutoAlertSettings(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS pagasa_auto_alert_settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $defaults = [
        'enabled' => '0',
        'check_interval_minutes' => '15',
        'channels' => 'push,email',
        'last_check_at' => '',
        'last_bulletin_hash' => ''
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO pagasa_auto_alert_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaults as $key => $value) {
        $stmt->execute([$key, $value]);
    }
}

function getWeatherRiskAutoAlertSetting(PDO $pdo, string $key, string $default = ''): string {
    ensureWeatherRiskAutoAlertSettings($pdo);
    $stmt = $pdo->prepare('SELECT setting_value FROM pagasa_auto_alert_settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string)$value;
}

function setWeatherRiskAutoAlertSetting(PDO $pdo, string $key, string $value): void {
    ensureWeatherRiskAutoAlertSettings($pdo);
    $stmt = $pdo->prepare('INSERT INTO pagasa_auto_alert_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()');
    $stmt->execute([$key, $value, $value]);
}

function getWeatherRiskAutoAlertEnabled(PDO $pdo): bool {
    return getWeatherRiskAutoAlertSetting($pdo, 'enabled', '0') === '1';
}

function getWeatherRiskAutoAlertChannels(PDO $pdo): string {
    try {
        $raw = getWeatherRiskAutoAlertSetting($pdo, 'channels', 'push,email');
        $channels = array_values(array_intersect(array_filter(array_map('trim', explode(',', strtolower($raw)))), ['push', 'email', 'sms']));
        return $channels ? implode(',', $channels) : 'push,email';
    } catch (Throwable $e) {
        return 'push,email';
    }
}
function weatherRiskFormatPeakPeriod(?string $dt): string {
    if (!$dt) return 'Next 24 hours';
    $start = strtotime($dt);
    if (!$start) return 'Next 24 hours';
    $end = $start + (3 * 3600);
    return date('g A', $start) . '-' . date('g A', $end);
}

function weatherRiskPrecautions(array $analysis): array {
    $metrics = $analysis['metrics'] ?? [];
    $risks = [];
    foreach (($analysis['risks'] ?? []) as $risk) {
        $risks[$risk['key'] ?? ''] = $risk['level'] ?? 'normal';
    }

    $steps = [];
    $add = function (string $step) use (&$steps): void {
        if (!in_array($step, $steps, true)) $steps[] = $step;
    };

    $rainLevel = $risks['rainfall'] ?? 'normal';
    $floodLevel = $risks['flood_risk'] ?? 'normal';
    $stormLevel = $risks['thunderstorm'] ?? 'normal';
    $windLevel = $risks['wind'] ?? 'normal';
    $visibilityLevel = $risks['visibility'] ?? 'normal';
    $heatLevel = $risks['heat'] ?? 'normal';

    if (weatherRiskLevelRank($rainLevel) >= weatherRiskLevelRank('advisory')) {
        $add('Bring an umbrella or raincoat when going outside.');
        $add('Expect wet or slippery roads and allow extra travel time.');
    } elseif ((int)($metrics['max_precipitation_probability'] ?? 0) >= 40) {
        $add('Consider bringing an umbrella if you will be outside.');
    }

    if (weatherRiskLevelRank($rainLevel) >= weatherRiskLevelRank('warning') || weatherRiskLevelRank($floodLevel) >= weatherRiskLevelRank('advisory')) {
        $add('Avoid unnecessary travel during periods of heavy rainfall.');
        $add('Stay away from flood-prone and low-lying areas.');
        $add('Never walk or drive through deep floodwater.');
        $add('Monitor official flood advisories and Alertara updates.');
    }

    if (weatherRiskLevelRank($stormLevel) >= weatherRiskLevelRank('advisory')) {
        $add('Stay indoors when thunderstorms are nearby.');
        $add('Avoid open areas and isolated tall objects.');
        $add('Avoid using exposed electrical equipment during severe lightning.');
    }

    if (weatherRiskLevelRank($windLevel) >= weatherRiskLevelRank('advisory')) {
        $add('Secure loose outdoor items before winds strengthen.');
        $add('Stay away from trees, power lines, billboards, and unsecured objects.');
    }
    if (weatherRiskLevelRank($windLevel) >= weatherRiskLevelRank('warning')) {
        $add('Avoid unnecessary outdoor activity while dangerous winds are occurring.');
    }

    if (weatherRiskLevelRank($visibilityLevel) >= weatherRiskLevelRank('advisory')) {
        $add('Reduce driving speed and use appropriate vehicle lights.');
        $add('Keep a safe distance from other vehicles.');
    }

    if (weatherRiskLevelRank($heatLevel) >= weatherRiskLevelRank('advisory')) {
        $add('Drink enough water and stay in shaded or cooler areas when possible.');
        $add('Limit strenuous outdoor activity during the hottest hours.');
    }

    if (!$steps) $add('Check the latest forecast before traveling.');
    return array_slice($steps, 0, 5);
}

function weatherRiskNotificationMessage(array $analysis) {
    $overall = $analysis['overall'] ?? [];
    $metrics = $analysis['metrics'] ?? [];
    $forecastLabel = ($overall['label'] ?? 'Weather') . ' ' . weatherRiskLabel($overall['level'] ?? 'normal');
    $peakPeriod = weatherRiskFormatPeakPeriod($metrics['peak_period'] ?? null);
    $tempMin = $metrics['min_temp_c'] ?? null;
    $tempMax = $metrics['max_temp_c'] ?? null;
    $tempRange = ($tempMin !== null ? $tempMin : '--') . '-' . ($tempMax !== null ? $tempMax : '--') . ' C';
    $wind = ($metrics['max_wind_kmh'] ?? 0) . ' km/h';
    $gust = (float)($metrics['max_gust_kmh'] ?? 0);
    if ($gust > 0) $wind .= ', gusts up to ' . $gust . ' km/h';

    $summary = $forecastLabel . ' is expected in Quezon City based on Open-Meteo forecast.';
    if (($overall['key'] ?? '') === 'flood_risk') {
        $summary .= ' Flood Risk Watch is forecast-based only and does not confirm flooding is occurring.';
    }

    $lines = [
        'WEATHER FORECAST - QUEZON CITY',
        '',
        $summary,
        '',
        'Rain chance: ' . ($metrics['max_precipitation_probability'] ?? 0) . '%',
        'Expected rainfall: ' . ($metrics['rain_24h_mm'] ?? 0) . ' mm in 24h',
        'Temperature: ' . $tempRange,
        'Wind: ' . $wind,
        'Peak period: ' . $peakPeriod,
        '',
        'PRECAUTIONS'
    ];
    foreach (weatherRiskPrecautions($analysis) as $step) {
        $lines[] = '- ' . $step;
    }
    $lines[] = '';
    $lines[] = 'View Full Forecast: https://emergency-comm.alertaraqc.com/USERS/weather-map.php';
    return implode("\n", $lines);
}

function weatherRiskPushPreview(array $analysis): string {
    $overall = $analysis['overall'] ?? [];
    $metrics = $analysis['metrics'] ?? [];
    $label = ($overall['label'] ?? 'Weather') . ' ' . weatherRiskLabel($overall['level'] ?? 'normal');
    return $label . ': rain chance ' . ($metrics['max_precipitation_probability'] ?? 0) . '%, rain ' . ($metrics['rain_24h_mm'] ?? 0) . ' mm in 24h, gusts up to ' . ($metrics['max_gust_kmh'] ?? 0) . ' km/h. Open Alertara for precautions.';
}

function analyzeTomorrowWeatherForecast(array $forecastData): array {
    $items = array_values(array_filter($forecastData['list'] ?? [], 'is_array'));
    $tomorrowKey = date('Y-m-d', strtotime('+1 day'));
    $tomorrow = [];
    foreach ($items as $forecast) {
        $dt = (int)($forecast['dt'] ?? strtotime($forecast['dt_txt'] ?? ''));
        if ($dt > 0 && date('Y-m-d', $dt) === $tomorrowKey) $tomorrow[] = $forecast;
    }

    if (!$tomorrow) {
        return ['success' => false, 'message' => 'No tomorrow forecast window is available yet.', 'forecast_date' => $tomorrowKey];
    }

    $rainTotal = 0.0; $maxPop = 0; $maxRain = 0.0; $maxWind = 0.0; $maxGust = 0.0;
    $minTemp = null; $maxTemp = null; $minVisibility = null; $hasThunder = false; $peak = null; $peakScore = -1;
    foreach ($tomorrow as $forecast) {
        $precip = (float)getForecastMetric($forecast, 'precipitation_mm', 0);
        $rain = max($precip, (float)getForecastMetric($forecast, 'rain_mm', 0), (float)getForecastMetric($forecast, 'showers_mm', 0));
        $pop = (int)getForecastMetric($forecast, 'precipitation_probability', 0);
        $wind = (float)getForecastMetric($forecast, 'wind_speed_kmh', 0);
        $gust = (float)getForecastMetric($forecast, 'wind_gust_kmh', 0);
        $visibility = getForecastMetric($forecast, 'visibility_m', null);
        $code = (int)getForecastMetric($forecast, 'weather_code', 0);
        $main = strtolower((string)($forecast['weather'][0]['main'] ?? ''));
        $temp = isset($forecast['main']['temp']) ? (float)$forecast['main']['temp'] : null;
        $tempMin = isset($forecast['main']['temp_min']) ? (float)$forecast['main']['temp_min'] : $temp;
        $tempMax = isset($forecast['main']['temp_max']) ? (float)$forecast['main']['temp_max'] : $temp;

        $rainTotal += $rain;
        $maxRain = max($maxRain, $rain);
        $maxPop = max($maxPop, $pop);
        $maxWind = max($maxWind, $wind);
        $maxGust = max($maxGust, $gust);
        if ($tempMin !== null) $minTemp = $minTemp === null ? $tempMin : min($minTemp, $tempMin);
        if ($tempMax !== null) $maxTemp = $maxTemp === null ? $tempMax : max($maxTemp, $tempMax);
        if ($visibility !== null) $minVisibility = $minVisibility === null ? (int)$visibility : min($minVisibility, (int)$visibility);
        if (in_array($code, [95, 96, 99], true) || $main === 'thunderstorm') $hasThunder = true;

        $score = ($rain * 10) + $pop + ($hasThunder ? 25 : 0) + max(0, $gust - 30);
        if ($score > $peakScore) {
            $peakScore = $score;
            $peak = $forecast['dt_txt'] ?? (isset($forecast['dt']) ? date('Y-m-d H:i:s', (int)$forecast['dt']) : null);
        }
    }

    $level = 'normal';
    if ($rainTotal >= 50 || $maxRain >= 20 || ($hasThunder && $maxGust >= 55) || $maxGust >= 75) $level = 'warning';
    elseif ($rainTotal >= 25 || $maxRain >= 10 || $maxPop >= 80 || $hasThunder || $maxGust >= 55) $level = 'advisory';
    elseif ($rainTotal >= 5 || $maxPop >= 40 || $maxWind >= 25 || $maxGust >= 40 || ($maxTemp !== null && $maxTemp >= 33)) $level = 'watch';

    $key = 'fair_weather';
    $label = 'Daily Forecast';
    if ($rainTotal >= 25 || $maxRain >= 10) { $key = 'heavy_rain'; $label = 'Heavy Rain Forecast'; }
    elseif ($hasThunder) { $key = 'thunderstorm'; $label = 'Thunderstorm Forecast'; }
    elseif ($maxGust >= 40 || $maxWind >= 30) { $key = 'wind'; $label = 'Wind Forecast'; }
    elseif ($rainTotal >= 5 || $maxPop >= 40) { $key = 'rain'; $label = 'Rain Forecast'; }
    elseif ($maxTemp !== null && $maxTemp >= 33) { $key = 'heat'; $label = 'Hot Weather Forecast'; }

    $summary = 'Quezon City forecast for tomorrow.';
    if ($key === 'heavy_rain') $summary = 'Rainy conditions may affect Quezon City tomorrow.';
    elseif ($key === 'thunderstorm') $summary = 'Thunderstorms are possible in Quezon City tomorrow.';
    elseif ($key === 'wind') $summary = 'Breezy to windy conditions are possible in Quezon City tomorrow.';
    elseif ($key === 'rain') $summary = 'Rain is possible in Quezon City tomorrow.';
    elseif ($key === 'heat') $summary = 'Hot weather is expected in Quezon City tomorrow.';

    return [
        'success' => true,
        'location' => 'Quezon City',
        'source' => 'Open-Meteo tomorrow forecast',
        'generated_at' => date('Y-m-d H:i:s'),
        'forecast_date' => $tomorrowKey,
        'forecast_day' => date('D, M j', strtotime($tomorrowKey)),
        'summary' => $summary,
        'overall' => ['key' => 'tomorrow_' . $key, 'label' => $label, 'level' => $level, 'severity' => weatherRiskSeverity($level)],
        'metrics' => [
            'rain_24h_mm' => round($rainTotal, 1),
            'max_rain_3h_mm' => round($maxRain, 1),
            'max_precipitation_probability' => $maxPop,
            'min_temp_c' => $minTemp === null ? null : round($minTemp, 1),
            'max_temp_c' => $maxTemp === null ? null : round($maxTemp, 1),
            'max_wind_kmh' => round($maxWind, 1),
            'max_gust_kmh' => round($maxGust, 1),
            'min_visibility_m' => $minVisibility,
            'peak_period' => $peak
        ],
        'risks' => [
            ['key' => 'rainfall', 'level' => $rainTotal >= 25 ? 'advisory' : ($rainTotal >= 5 || $maxPop >= 40 ? 'watch' : 'normal')],
            ['key' => 'flood_risk', 'level' => $rainTotal >= 50 ? 'warning' : ($rainTotal >= 25 ? 'advisory' : ($rainTotal >= 15 ? 'watch' : 'normal'))],
            ['key' => 'thunderstorm', 'level' => $hasThunder ? 'advisory' : 'normal'],
            ['key' => 'wind', 'level' => $maxGust >= 75 ? 'warning' : ($maxGust >= 55 ? 'advisory' : ($maxGust >= 40 ? 'watch' : 'normal'))],
            ['key' => 'visibility', 'level' => ($minVisibility !== null && $minVisibility <= 3000) ? 'advisory' : (($minVisibility !== null && $minVisibility <= 5000) ? 'watch' : 'normal')],
            ['key' => 'heat', 'level' => ($maxTemp !== null && $maxTemp >= 35) ? 'advisory' : (($maxTemp !== null && $maxTemp >= 33) ? 'watch' : 'normal')]
        ]
    ];
}

function tomorrowWeatherForecastEventHash(array $analysis): string {
    return hash('sha256', implode('|', [
        'tomorrow_forecast',
        $analysis['forecast_date'] ?? date('Y-m-d', strtotime('+1 day')),
        $analysis['overall']['key'] ?? 'normal',
        $analysis['overall']['level'] ?? 'normal'
    ]));
}

function tomorrowWeatherForecastMessage(array $analysis): string {
    $metrics = $analysis['metrics'] ?? [];
    $peakPeriod = weatherRiskFormatPeakPeriod($metrics['peak_period'] ?? null);
    $tempMin = $metrics['min_temp_c'] ?? null;
    $tempMax = $metrics['max_temp_c'] ?? null;
    $tempRange = ($tempMin !== null ? $tempMin : '--') . '-' . ($tempMax !== null ? $tempMax : '--') . ' C';
    $wind = ($metrics['max_wind_kmh'] ?? 0) . ' km/h';
    $gust = (float)($metrics['max_gust_kmh'] ?? 0);
    if ($gust > 0) $wind .= ', gusts up to ' . $gust . ' km/h';

    $lines = [
        'WEATHER FORECAST - QUEZON CITY',
        '',
        ($analysis['summary'] ?? 'Quezon City weather forecast for tomorrow.') . ' Forecast day: ' . ($analysis['forecast_day'] ?? 'Tomorrow') . '.',
        '',
        'Rain chance: ' . ($metrics['max_precipitation_probability'] ?? 0) . '%',
        'Expected rainfall: ' . ($metrics['rain_24h_mm'] ?? 0) . ' mm',
        'Temperature: ' . $tempRange,
        'Wind: ' . $wind,
        'Peak period: ' . $peakPeriod,
        '',
        'PRECAUTIONS'
    ];
    foreach (weatherRiskPrecautions($analysis) as $step) $lines[] = '- ' . $step;
    $lines[] = '';
    $lines[] = 'View Full Forecast: https://emergency-comm.alertaraqc.com/USERS/weather-map.php';
    return implode("`n", $lines);
}

function tomorrowWeatherForecastPushPreview(array $analysis): string {
    $metrics = $analysis['metrics'] ?? [];
    return 'Tomorrow in Quezon City: rain chance ' . ($metrics['max_precipitation_probability'] ?? 0) . '%, rainfall ' . ($metrics['rain_24h_mm'] ?? 0) . ' mm, wind up to ' . ($metrics['max_wind_kmh'] ?? 0) . ' km/h. Tap for forecast.';
}

function queueTomorrowWeatherForecastAlert(PDO $pdo, array $forecastData): array {
    require_once __DIR__ . '/bulletin-dispatch-helper.php';
    ensureWeatherRiskTables($pdo);
    $analysis = analyzeTomorrowWeatherForecast($forecastData);
    if (empty($analysis['success'])) {
        return ['success' => true, 'alerted' => false, 'message' => $analysis['message'] ?? 'Tomorrow forecast unavailable.', 'analysis' => $analysis];
    }

    $hash = tomorrowWeatherForecastEventHash($analysis);
    $check = $pdo->prepare('SELECT id, created_at FROM weather_risk_auto_alert_log WHERE event_hash = ? LIMIT 1');
    $check->execute([$hash]);
    if ($check->fetch(PDO::FETCH_ASSOC)) {
        return ['success' => true, 'alerted' => false, 'deduped' => true, 'message' => 'Tomorrow weather forecast alert already queued for this forecast state.', 'analysis' => $analysis];
    }

    $title = 'Tomorrow Weather Forecast - Quezon City';
    if (weatherRiskLevelRank($analysis['overall']['level'] ?? 'normal') >= weatherRiskLevelRank('advisory')) {
        $title = 'Tomorrow ' . ($analysis['overall']['label'] ?? 'Weather Forecast') . ' - Quezon City';
    }
    $message = tomorrowWeatherForecastMessage($analysis);
    $queued = queueBulletinBroadcast($pdo, [
        'title' => $title,
        'message' => $message,
        'severity' => $analysis['overall']['severity'] ?? 'low',
        'source' => 'open_meteo_tomorrow_forecast',
        'category' => 'weather',
        'channels' => getWeatherRiskAutoAlertChannels($pdo),
        'push_preview' => tomorrowWeatherForecastPushPreview($analysis),
        'more_info_url' => 'https://emergency-comm.alertaraqc.com/USERS/weather-map.php'
    ]);

    $stmt = $pdo->prepare('INSERT INTO weather_risk_auto_alert_log (event_hash, risk_key, risk_level, title, message, metrics_json, alert_id, log_id, queued_jobs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$hash, $analysis['overall']['key'], $analysis['overall']['level'], $title, $message, json_encode($analysis['metrics'], JSON_UNESCAPED_SLASHES), $queued['alert_id'] ?? null, $queued['log_id'] ?? null, (int)($queued['queued_jobs'] ?? 0)]);
    return ['success' => true, 'alerted' => true, 'message' => 'Tomorrow weather forecast alert queued.', 'queued' => $queued, 'analysis' => $analysis];
}
function weatherRiskEventHash(array $analysis) {
    $metrics = $analysis['metrics'];
    $bucket = date('YmdH', time() - (time() % (3 * 3600)));
    return hash('sha256', implode('|', [
        $analysis['overall']['key'] ?? 'normal',
        $analysis['overall']['level'] ?? 'normal',
        $bucket,
        round((float)($metrics['rain_24h_mm'] ?? 0) / 10) * 10,
        round((float)($metrics['max_gust_kmh'] ?? 0) / 10) * 10,
        round((float)($metrics['max_feels_like_c'] ?? 0))
    ]));
}

function ensureWeatherRiskTables(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS weather_risk_auto_alert_log (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        event_hash CHAR(64) NOT NULL,
        risk_key VARCHAR(60) NOT NULL,
        risk_level VARCHAR(30) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        metrics_json LONGTEXT NULL,
        alert_id BIGINT UNSIGNED NULL,
        log_id BIGINT UNSIGNED NULL,
        queued_jobs INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_weather_risk_event (event_hash),
        INDEX idx_created_at (created_at),
        INDEX idx_risk (risk_key, risk_level)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

if ($action === 'risk') {
    $lat = isset($_GET['lat']) ? floatval($_GET['lat']) : 14.6760;
    $lon = isset($_GET['lon']) ? floatval($_GET['lon']) : 121.0437;
    $forecastData = fetchForecastData($lat, $lon, $apiKey);
    if (isset($forecastData['error'])) {
        echo json_encode(['success' => false, 'message' => 'Failed to analyze weather risk: ' . $forecastData['error']]);
    } else {
        echo json_encode(['success' => true, 'data' => analyzeWeatherRisks($forecastData)]);
    }
} elseif ($action === 'auto-risk-check') {
    $forceRun = isset($_GET['force']) && $_GET['force'] === '1';
    ensureWeatherRiskAutoAlertSettings($pdo);
    setWeatherRiskAutoAlertSetting($pdo, 'last_check_at', date('Y-m-d H:i:s'));
    if (!$forceRun && !getWeatherRiskAutoAlertEnabled($pdo)) {
        echo json_encode(['success' => true, 'alerted' => false, 'disabled' => true, 'message' => 'Weather risk auto-alert is disabled.']);
        exit;
    }
    $forecastData = fetchForecastData(14.6760, 121.0437, $apiKey);
    if (isset($forecastData['error'])) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch weather risk forecast: ' . $forecastData['error']]);
        exit;
    }
    require_once __DIR__ . '/bulletin-dispatch-helper.php';
    ensureWeatherRiskTables($pdo);
    $analysis = analyzeWeatherRisks($forecastData);
    $weatherRiskResult = ['success' => true, 'alerted' => false, 'message' => 'No qualifying weather risk alert.', 'analysis' => $analysis];

    if (weatherRiskLevelRank($analysis['overall']['level'] ?? 'normal') >= weatherRiskLevelRank('advisory')) {
        $hash = weatherRiskEventHash($analysis);
        $check = $pdo->prepare('SELECT id, created_at FROM weather_risk_auto_alert_log WHERE event_hash = ? LIMIT 1');
        $check->execute([$hash]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $weatherRiskResult = ['success' => true, 'alerted' => false, 'deduped' => true, 'message' => 'Weather risk alert already queued for this forecast state.', 'analysis' => $analysis];
        } else {
            $title = 'Quezon City ' . $analysis['overall']['label'] . ' ' . weatherRiskLabel($analysis['overall']['level']);
            $message = weatherRiskNotificationMessage($analysis);
            $queued = queueBulletinBroadcast($pdo, [
                'title' => $title,
                'message' => $message,
                'severity' => $analysis['overall']['severity'],
                'source' => 'open_meteo_weather_risk',
                'category' => 'weather',
                'channels' => getWeatherRiskAutoAlertChannels($pdo),
                'push_preview' => weatherRiskPushPreview($analysis),
                'more_info_url' => 'https://emergency-comm.alertaraqc.com/USERS/weather-map.php'
            ]);
            $stmt = $pdo->prepare('INSERT INTO weather_risk_auto_alert_log (event_hash, risk_key, risk_level, title, message, metrics_json, alert_id, log_id, queued_jobs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$hash, $analysis['overall']['key'], $analysis['overall']['level'], $title, $message, json_encode($analysis['metrics'], JSON_UNESCAPED_SLASHES), $queued['alert_id'] ?? null, $queued['log_id'] ?? null, (int)($queued['queued_jobs'] ?? 0)]);
            $weatherRiskResult = ['success' => true, 'alerted' => true, 'message' => 'Weather risk alert queued.', 'queued' => $queued, 'analysis' => $analysis];
        }
    }

    $tomorrowForecastResult = queueTomorrowWeatherForecastAlert($pdo, $forecastData);
    echo json_encode([
        'success' => true,
        'alerted' => !empty($weatherRiskResult['alerted']) || !empty($tomorrowForecastResult['alerted']),
        'message' => trim(($weatherRiskResult['message'] ?? '') . ' ' . ($tomorrowForecastResult['message'] ?? '')),
        'weather_risk' => $weatherRiskResult,
        'tomorrow_forecast' => $tomorrowForecastResult
    ]);
} elseif ($action === 'weather-risk-history') {
    ensureWeatherRiskTables($pdo);
    $limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 10;
    $stmt = $pdo->prepare("SELECT id, title AS bulletin_title, message AS bulletin_summary, risk_level AS severity, queued_jobs AS recipients_count, alert_id AS dispatch_log_id, created_at, CASE WHEN queued_jobs > 0 THEN 'queued' ELSE 'sent' END AS status FROM weather_risk_auto_alert_log ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($logs as &$log) {
        $log['channels'] = getWeatherRiskAutoAlertChannels($pdo);
    }
    unset($log);
    echo json_encode(['success' => true, 'logs' => $logs]);
} elseif ($action === 'current') {
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
                    'wind_deg' => $forecast['wind']['deg'] ?? 0,
                    'wind_gust' => $forecast['wind']['gust'] ?? null,
                    'visibility' => $forecast['open_meteo']['visibility_m'] ?? null,
                    'weather_code' => $forecast['open_meteo']['weather_code'] ?? ($forecast['weather'][0]['id'] ?? null),
                    'source' => 'Open-Meteo'
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



