<?php
/**
 * Earthquake AI Analytics API
 * Analyzes earthquake data and predicts impact on Quezon City
 * Uses Gemini AI to provide hazard assessments and impact predictions
 */

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

// Set error handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Only handle if headers haven't been sent and no output has been flushed
        if (!headers_sent() && ob_get_level() > 0) {
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            $output = json_encode([
                "success" => false,
                "message" => "Fatal error: " . $error['message'] . " in " . basename($error['file']) . " on line " . $error['line']
            ], JSON_UNESCAPED_UNICODE);
            echo $output;
            if (ob_get_level()) {
                ob_end_flush();
            }
        }
        error_log("Earthquake AI Analytics Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
    }
});

header('Content-Type: application/json; charset=utf-8');

try {
    require_once 'db_connect.php';
    require_once 'secure-api-config.php';
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    $output = json_encode(['success' => false, 'message' => 'Configuration error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    echo $output;
    error_log('Earthquake AI Analytics: Failed to load required files: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit();
} catch (Error $e) {
    ob_clean();
    http_response_code(500);
    $output = json_encode(['success' => false, 'message' => 'Fatal error loading files: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    echo $output;
    error_log('Earthquake AI Analytics: Fatal error loading files: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit();
} catch (Throwable $e) {
    ob_clean();
    http_response_code(500);
    $output = json_encode(['success' => false, 'message' => 'Error loading files: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    echo $output;
    error_log('Earthquake AI Analytics: Throwable error loading files: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit();
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ob_clean();
    http_response_code(401);
    $output = json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    echo $output;
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit();
}

$action = $_GET['action'] ?? 'analyze';

try {
    switch ($action) {
        case 'analyze':
            analyzeEarthquakeImpact();
            break;
        case 'assess_risk':
            assessEarthquakeRisk();
            break;
        case 'test':
            // Diagnostic endpoint to test API connectivity
            ob_clean();
            $diagnostics = [
                'success' => true,
                'message' => 'API is working',
                'session_active' => isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true,
                'db_connected' => isset($pdo) && $pdo !== null,
                'getGeminiApiKey_exists' => function_exists('getGeminiApiKey'),
                'api_key_configured' => false,
                'config_paths_checked' => []
            ];
            
            if (function_exists('getGeminiApiKey')) {
                try {
                    $testKey = getGeminiApiKey('analysis');
                    $diagnostics['api_key_configured'] = !empty($testKey);
                    if (empty($testKey)) {
                        $testKey = getGeminiApiKey('default');
                        $diagnostics['api_key_configured'] = !empty($testKey);
                    }
                } catch (Exception $e) {
                    $diagnostics['api_key_error'] = $e->getMessage();
                }
            }
            
            $output = json_encode($diagnostics, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            echo $output;
            if (ob_get_level()) {
                ob_end_flush();
            }
            exit();
        default:
            ob_clean();
            http_response_code(400);
            $output = json_encode(['success' => false, 'message' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
            echo $output;
            if (ob_get_level()) {
                ob_end_flush();
            }
            exit();
    }
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    $errorMessage = 'Error: ' . $e->getMessage();
    error_log('Earthquake AI Analytics Exception: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    $output = json_encode(['success' => false, 'message' => $errorMessage], JSON_UNESCAPED_UNICODE);
    echo $output;
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit();
} catch (Error $e) {
    ob_clean();
    http_response_code(500);
    $errorMessage = 'Fatal error: ' . $e->getMessage();
    error_log('Earthquake AI Analytics Fatal Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    $output = json_encode(['success' => false, 'message' => $errorMessage], JSON_UNESCAPED_UNICODE);
    echo $output;
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit();
} catch (Throwable $e) {
    ob_clean();
    http_response_code(500);
    $errorMessage = 'Throwable error: ' . $e->getMessage();
    error_log('Earthquake AI Analytics Throwable Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    $output = json_encode(['success' => false, 'message' => $errorMessage], JSON_UNESCAPED_UNICODE);
    echo $output;
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit();
}

/**
 * Analyze earthquake impact on Quezon City
 */
function analyzeEarthquakeImpact() {
    global $pdo;
    
    // Check if AI analysis is enabled for earthquake
    if (!isAIAnalysisEnabled('earthquake')) {
        ob_clean();
        http_response_code(403);
        $output = json_encode([
            'success' => false, 
            'message' => 'AI earthquake analysis is currently disabled. Please enable it in General Settings → AI Analysis Settings to use this feature.'
        ], JSON_UNESCAPED_UNICODE);
        echo $output;
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit();
    }
    
    // Log that we've entered the function
    error_log('Earthquake AI Analytics: analyzeEarthquakeImpact() called');
    
    try {
        // Get earthquake data from request
        error_log('Earthquake AI Analytics: Reading input data');
        $input = file_get_contents('php://input');
        error_log('Earthquake AI Analytics: Input length: ' . strlen($input));
        
        if (empty($input)) {
            ob_clean();
            http_response_code(400);
            $output = json_encode(['success' => false, 'message' => 'No data received'], JSON_UNESCAPED_UNICODE);
            echo $output;
            if (ob_get_level()) {
                ob_end_flush();
            }
            exit();
        }
        
        $earthquakeData = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            ob_clean();
            http_response_code(400);
            $output = json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
            echo $output;
            if (ob_get_level()) {
                ob_end_flush();
            }
            exit();
        }
        
        if (empty($earthquakeData) || !isset($earthquakeData['earthquakes'])) {
            ob_clean();
            http_response_code(400);
            $output = json_encode(['success' => false, 'message' => 'No earthquake data provided'], JSON_UNESCAPED_UNICODE);
            echo $output;
            if (ob_get_level()) {
                ob_end_flush();
            }
            exit();
        }
        
        $earthquakes = $earthquakeData['earthquakes'] ?? [];
        
        if (empty($earthquakes) || !is_array($earthquakes)) {
            ob_clean();
            http_response_code(400);
            $output = json_encode(['success' => false, 'message' => 'Empty or invalid earthquake array'], JSON_UNESCAPED_UNICODE);
            echo $output;
            if (ob_get_level()) {
                ob_end_flush();
            }
            exit();
        }
        
        // Get API key for analysis
        $apiKey = null;
        $backupApiKey = null;
        try {
            // Check if function exists
            if (!function_exists('getGeminiApiKey')) {
                throw new Exception('getGeminiApiKey function not found. secure-api-config.php may not be loaded correctly.');
            }
            
            // Try earthquake-specific key first, then analysis, then default
            $apiKey = getGeminiApiKey('earthquake');
            if (empty($apiKey)) {
                $apiKey = getGeminiApiKey('analysis');
            }
            if (empty($apiKey)) {
                $apiKey = getGeminiApiKey('default');
            }
            
            // Get backup key for quota exceeded scenarios
            $backupApiKey = getGeminiApiKey('analysis_backup');
        } catch (ParseError $e) {
            error_log('Parse error getting Gemini API key: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            $apiKey = null;
        } catch (Exception $e) {
            error_log('Error getting Gemini API key: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            $apiKey = null;
        } catch (Error $e) {
            error_log('Fatal error getting Gemini API key: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            $apiKey = null;
        } catch (Throwable $e) {
            error_log('Throwable error getting Gemini API key: ' . $e->getMessage());
            $apiKey = null;
        }
        
        if (empty($apiKey)) {
            ob_clean();
            // Use 503 Service Unavailable for missing configuration (more appropriate than 500)
            http_response_code(503);
            $output = json_encode([
                'success' => false, 
                'message' => 'AI API key not configured. Please configure Gemini API key in Automated Warnings → AI Warning Settings.',
                'error_code' => 'API_KEY_MISSING'
            ], JSON_UNESCAPED_UNICODE);
            if ($output === false) {
                $output = json_encode([
                    'success' => false, 
                    'message' => 'AI API key not configured.',
                    'error_code' => 'API_KEY_MISSING'
                ]);
            }
            echo $output;
            if (ob_get_level()) {
                ob_end_flush();
            }
            exit();
        }
    } catch (Exception $e) {
        ob_clean();
        http_response_code(500);
        error_log('Error in analyzeEarthquakeImpact (input processing): ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        $output = json_encode(['success' => false, 'message' => 'Error processing request: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        echo $output;
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit();
    } catch (Error $e) {
        ob_clean();
        http_response_code(500);
        error_log('Fatal error in analyzeEarthquakeImpact (input processing): ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        $output = json_encode(['success' => false, 'message' => 'Fatal error processing request: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        echo $output;
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit();
    } catch (Throwable $e) {
        ob_clean();
        http_response_code(500);
        error_log('Throwable error in analyzeEarthquakeImpact (input processing): ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        $output = json_encode(['success' => false, 'message' => 'Error processing request: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        echo $output;
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit();
    }
    
    try {
        // Quezon City coordinates
        $quezonCityLat = 14.6488;
        $quezonCityLon = 121.0509;
        
        // Prepare earthquake data for analysis
        $earthquakeSummary = [];
        foreach ($earthquakes as $eq) {
            $lat = $eq['lat'] ?? $eq['geometry']['coordinates'][1] ?? null;
            $lon = $eq['lon'] ?? $eq['geometry']['coordinates'][0] ?? null;
            $mag = $eq['magnitude'] ?? $eq['properties']['mag'] ?? 0;
            $depth = $eq['depth'] ?? $eq['geometry']['coordinates'][2] ?? 0;
            $place = $eq['place'] ?? $eq['properties']['place'] ?? 'Unknown';
            $time = $eq['time'] ?? $eq['properties']['time'] ?? time() * 1000;
            
            if ($lat && $lon) {
                // Calculate distance from Quezon City (Haversine formula)
                $distance = calculateDistance($quezonCityLat, $quezonCityLon, $lat, $lon);
                
                $earthquakeSummary[] = [
                    'magnitude' => $mag,
                    'depth' => $depth,
                    'location' => $place,
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'distance_km' => round($distance, 1),
                    'time' => date('Y-m-d H:i:s', $time / 1000)
                ];
            }
        }
        
        // Prioritize earthquakes near Quezon City (within 300km)
        // Sort by: 1) Distance from Quezon City (closer first), 2) Magnitude (higher first)
        usort($earthquakeSummary, function($a, $b) {
            // If both are within 300km, prioritize by distance first, then magnitude
            $aNearby = $a['distance_km'] <= 300;
            $bNearby = $b['distance_km'] <= 300;
            
            if ($aNearby && $bNearby) {
                // Both nearby - sort by distance first, then magnitude
                if (abs($a['distance_km'] - $b['distance_km']) < 10) {
                    return $b['magnitude'] <=> $a['magnitude'];
                }
                return $a['distance_km'] <=> $b['distance_km'];
            } elseif ($aNearby && !$bNearby) {
                return -1; // A is nearby, prioritize it
            } elseif (!$aNearby && $bNearby) {
                return 1; // B is nearby, prioritize it
            } else {
                // Neither nearby - sort by magnitude
                return $b['magnitude'] <=> $a['magnitude'];
            }
        });
        
        // Limit to top 15 earthquakes (prioritizing those near Quezon City)
        $earthquakeSummary = array_slice($earthquakeSummary, 0, 15);
        
        // Build AI prompt
        $prompt = buildAnalysisPrompt($earthquakeSummary, $quezonCityLat, $quezonCityLon);
        
        // Call Gemini AI with backup key support
        $analysis = callGeminiAI($apiKey, $prompt, $backupApiKey);
        
        if ($analysis['success']) {
            ob_clean(); // Clean output buffer but keep it active
            $output = json_encode([
                'success' => true,
                'analysis' => $analysis['content'],
                'earthquakes_analyzed' => count($earthquakeSummary),
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
            if ($output === false) {
                throw new Exception('JSON encoding failed: ' . json_last_error_msg());
            }
            echo $output;
            if (ob_get_level()) {
                ob_end_flush();
            }
            exit();
        } else {
            ob_clean();
            http_response_code(500);
            $output = json_encode([
                'success' => false,
                'message' => $analysis['error'] ?? 'Failed to generate analysis'
            ], JSON_UNESCAPED_UNICODE);
            if ($output === false) {
                $output = json_encode(['success' => false, 'message' => 'Failed to generate analysis']);
            }
            echo $output;
            if (ob_get_level()) {
                ob_end_flush();
            }
            exit();
        }
    } catch (Exception $e) {
        ob_clean();
        http_response_code(500);
        error_log('Error in analyzeEarthquakeImpact (analysis): ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        $output = json_encode(['success' => false, 'message' => 'Error during analysis: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        echo $output;
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit();
    } catch (Error $e) {
        ob_clean();
        http_response_code(500);
        error_log('Fatal error in analyzeEarthquakeImpact (analysis): ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        $output = json_encode(['success' => false, 'message' => 'Fatal error during analysis: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        echo $output;
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit();
    } catch (Throwable $e) {
        ob_clean();
        http_response_code(500);
        error_log('Throwable error in analyzeEarthquakeImpact (analysis): ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        $output = json_encode(['success' => false, 'message' => 'Error during analysis: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        echo $output;
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit();
    }
}

/**
 * Build AI analysis prompt
 */
function buildAnalysisPrompt($earthquakes, $qcLat, $qcLon) {
    $prompt = "You are an expert seismologist and disaster risk assessment specialist analyzing earthquake impacts SPECIFICALLY FOR QUEZON CITY, Philippines.\n\n";
    $prompt .= "IMPORTANT: Focus your analysis exclusively on how these earthquakes affect Quezon City. Quezon City Coordinates: Latitude {$qcLat}, Longitude {$qcLon}\n\n";
    $prompt .= "Recent Earthquakes (prioritized by proximity to Quezon City):\n";
    
    foreach ($earthquakes as $i => $eq) {
        $prompt .= sprintf(
            "%d. Magnitude %.1f earthquake at %s (%.2f°N, %.2f°E), Depth: %.1f km, Distance from Quezon City: %.1f km, Time: %s\n",
            $i + 1,
            $eq['magnitude'],
            $eq['location'],
            $eq['latitude'],
            $eq['longitude'],
            $eq['depth'],
            $eq['distance_km'],
            $eq['time']
        );
    }
    
    $prompt .= "\nCRITICAL: All analysis must be SPECIFIC TO QUEZON CITY. Focus on:\n";
    $prompt .= "- How these earthquakes specifically impact Quezon City residents and infrastructure\n";
    $prompt .= "- Quezon City's specific geographic and geological context\n";
    $prompt .= "- Local fault lines and seismic risks affecting Quezon City\n";
    $prompt .= "- Quezon City's building codes, soil conditions, and vulnerability\n\n";
    
    $prompt .= "Please provide a comprehensive analysis in JSON format with the following structure:\n";
    $prompt .= "{\n";
    $prompt .= "  \"overall_assessment\": \"Brief overall risk assessment SPECIFICALLY FOR QUEZON CITY (not general Philippines)\",\n";
    $prompt .= "  \"immediate_impacts\": [\"List of immediate impacts if any\"],\n";
    $prompt .= "  \"potential_hazards\": [\"List of potential hazards (ground shaking, liquefaction, landslides, tsunamis, etc.)\"],\n";
    $prompt .= "  \"risk_level\": \"low|moderate|high|critical\",\n";
    $prompt .= "  \"recommendations\": [\"List of recommended actions for Quezon City residents and authorities\"],\n";
    $prompt .= "  \"affected_areas\": [\"Specific areas in Quezon City that may be most affected\"],\n";
    $prompt .= "  \"magnitude_threshold\": \"Note if any earthquakes exceed dangerous thresholds\",\n";
    $prompt .= "  \"distance_analysis\": \"Analysis of how distance affects impact\",\n";
    $prompt .= "  \"landslide_risk\": \"Detailed assessment of landslide risk for Quezon City (consider hilly areas, slopes, and elevated regions)\",\n";
    $prompt .= "  \"travel_safety\": \"Assessment of travel safety (safe|caution|unsafe) with specific guidance on whether it's safe to travel, use bridges, or be in elevated structures\",\n";
    $prompt .= "  \"travel_safety_details\": \"Detailed explanation of travel safety assessment and specific precautions\"\n";
    $prompt .= "}\n\n";
    $prompt .= "Consider:\n";
    $prompt .= "- Distance decay: Earthquakes closer to Quezon City have more impact\n";
    $prompt .= "- Magnitude: Higher magnitude earthquakes can be felt from greater distances\n";
    $prompt .= "- Depth: Shallow earthquakes cause more surface shaking\n";
    $prompt .= "- Local geology: Quezon City's soil conditions and building structures\n";
    $prompt .= "- Historical patterns: Known fault lines and seismic activity in the region\n";
    $prompt .= "- Secondary hazards: Liquefaction risk, landslides, building damage potential\n";
    $prompt .= "- Landslide risk: Assess risk for hilly areas, slopes, and elevated regions in Quezon City\n";
    $prompt .= "- Travel safety: Evaluate if it's safe to travel, use bridges, or be in elevated structures based on earthquake magnitude, distance, and potential aftershocks\n";
    $prompt .= "- Infrastructure: Consider impact on roads, bridges, and buildings\n\n";
    $prompt .= "Provide accurate, scientific, and actionable analysis. Return ONLY valid JSON, no additional text.";
    
    return $prompt;
}

/**
 * Call Gemini AI API
 * @param string $apiKey Primary API key
 * @param string $prompt The prompt to send
 * @param string|null $backupApiKey Optional backup API key to use if quota exceeded
 */
function callGeminiAI($apiKey, $prompt, $backupApiKey = null) {
    try {
        $model = getGeminiModel();
    } catch (Exception $e) {
        error_log('Error getting Gemini model: ' . $e->getMessage());
        $model = 'gemini-2.5-flash'; // Fallback to default
    } catch (Error $e) {
        error_log('Fatal error getting Gemini model: ' . $e->getMessage());
        $model = 'gemini-2.5-flash'; // Fallback to default
    }
    
    if (empty($apiKey)) {
        return [
            'success' => false,
            'error' => 'API key is empty or invalid'
        ];
    }
    
    // Try v1 API first (for Gemini 2.5), fallback to v1beta
    $apiVersions = ['v1', 'v1beta'];
    $lastError = null;
    
    foreach ($apiVersions as $version) {
        try {
            $url = "https://generativelanguage.googleapis.com/{$version}/models/{$model}:generateContent?key=" . urlencode($apiKey);
            
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
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 2048
                ]
            ];
            
            $ch = curl_init($url);
            if ($ch === false) {
                $lastError = "Failed to initialize cURL";
                continue;
            }
            
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                $lastError = "cURL Error: " . $curlError;
                error_log("Gemini API cURL error ({$version}): " . $curlError);
                continue;
            }
            
            if ($httpCode !== 200) {
                $errorResponse = json_decode($response, true);
                $errorMessage = isset($errorResponse['error']['message']) 
                    ? $errorResponse['error']['message'] 
                    : substr($response, 0, 200);
                
                // Check if error is quota-related and retry with backup key
                // "overloaded" is Google's way of saying the free tier is rate-limited
                $isQuotaError = stripos($errorMessage, 'quota') !== false || 
                              stripos($errorMessage, 'exceeded') !== false ||
                              stripos($errorMessage, 'billing') !== false ||
                              stripos($errorMessage, 'overloaded') !== false ||
                              stripos($errorMessage, 'rate limit') !== false ||
                              stripos($errorMessage, 'resource_exhausted') !== false ||
                              $httpCode === 429;
                
                // If quota error and backup key available, try with backup key
                if ($isQuotaError && !empty($backupApiKey) && $apiKey !== $backupApiKey) {
                    error_log("Quota exceeded detected, retrying with backup API key");
                    
                    // Retry with backup key
                    $url = "https://generativelanguage.googleapis.com/{$version}/models/{$model}:generateContent?key=" . urlencode($backupApiKey);
                    $ch = curl_init($url);
                    if ($ch === false) {
                        $lastError = "Failed to initialize cURL for backup key";
                        continue;
                    }
                    
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => json_encode($data),
                        CURLOPT_HTTPHEADER => [
                            'Content-Type: application/json'
                        ],
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_CONNECTTIMEOUT => 10
                    ]);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    curl_close($ch);
                    
                    if ($curlError) {
                        $lastError = "cURL Error with backup key: " . $curlError;
                        error_log("Gemini API cURL error with backup key ({$version}): " . $curlError);
                        continue;
                    }
                    
                    if ($httpCode !== 200) {
                        $errorResponse = json_decode($response, true);
                        $errorMessage = isset($errorResponse['error']['message']) 
                            ? $errorResponse['error']['message'] 
                            : substr($response, 0, 200);
                        $lastError = "HTTP Error {$httpCode} (backup key): " . $errorMessage;
                        error_log("Gemini API HTTP error with backup key ({$version}): {$httpCode} - " . $errorMessage);
                        continue;
                    }
                    
                    error_log("Successfully used backup API key after quota exceeded");
                    // Continue to parse response below
                } else {
                    $lastError = "HTTP Error {$httpCode}: " . $errorMessage;
                    error_log("Gemini API HTTP error ({$version}): {$httpCode} - " . $errorMessage);
                    continue;
                }
            }
            
            // Parse response
            try {
                $result = json_decode($response, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $lastError = "Invalid JSON response: " . json_last_error_msg();
                    error_log("Gemini API JSON decode error ({$version}): " . json_last_error_msg());
                    continue;
                }
                
                if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    $lastError = "Unexpected response format: Missing candidates or content";
                    error_log("Gemini API unexpected response format ({$version}): " . substr(json_encode($result), 0, 200));
                    continue;
                }
                
                $content = trim($result['candidates'][0]['content']['parts'][0]['text']);
                
                // Remove markdown code blocks if present
                $content = preg_replace('/```json\s*/i', '', $content);
                $content = preg_replace('/```\s*/', '', $content);
                $content = trim($content);
                
                // Try multiple JSON extraction methods
                $jsonContent = null;
                
                // Method 1: Try parsing the entire content as JSON
                $jsonContent = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonContent)) {
                    return [
                        'success' => true,
                        'content' => $jsonContent,
                        'raw' => $content
                    ];
                }
                
                // Method 2: Extract JSON object from text (handles text before/after JSON)
                $jsonMatch = [];
                // Match JSON object that might span multiple lines
                if (preg_match('/\{[\s\S]*?\}/', $content, $jsonMatch)) {
                    $jsonContent = json_decode($jsonMatch[0], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($jsonContent)) {
                        return [
                            'success' => true,
                            'content' => $jsonContent,
                            'raw' => $content
                        ];
                    }
                }
                
                // Method 3: Try to find JSON between first { and last }
                $firstBrace = strpos($content, '{');
                $lastBrace = strrpos($content, '}');
                if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                    $jsonStr = substr($content, $firstBrace, $lastBrace - $firstBrace + 1);
                    $jsonContent = json_decode($jsonStr, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($jsonContent)) {
                        return [
                            'success' => true,
                            'content' => $jsonContent,
                            'raw' => $content
                        ];
                    }
                }
                
                // If JSON extraction failed, try to parse as structured text
                // Extract key-value pairs manually
                $parsed = parseStructuredResponse($content);
                if (!empty($parsed)) {
                    return [
                        'success' => true,
                        'content' => $parsed,
                        'raw' => $content
                    ];
                }
                
                // Last resort: return as text
                return [
                    'success' => true,
                    'content' => [
                        'overall_assessment' => $content,
                        'raw_response' => true
                    ],
                    'raw' => $content
                ];
            } catch (Exception $e) {
                $lastError = "Error parsing response: " . $e->getMessage();
                error_log("Gemini API response parsing error ({$version}): " . $e->getMessage());
                continue;
            } catch (Error $e) {
                $lastError = "Fatal error parsing response: " . $e->getMessage();
                error_log("Gemini API fatal response parsing error ({$version}): " . $e->getMessage());
                continue;
            }
        } catch (Exception $e) {
            $lastError = "Exception during API call: " . $e->getMessage();
            error_log("Gemini API exception ({$version}): " . $e->getMessage());
            continue;
        } catch (Error $e) {
            $lastError = "Fatal error during API call: " . $e->getMessage();
            error_log("Gemini API fatal error ({$version}): " . $e->getMessage());
            continue;
        }
    }
    
    return [
        'success' => false,
        'error' => $lastError ?? 'Unknown error - All API versions failed'
    ];
}

/**
 * Parse structured response when JSON parsing fails
 */
function parseStructuredResponse($text) {
    $result = [];
    
    // Try to extract overall_assessment
    if (preg_match('/overall[_\s]?assessment["\']?\s*[:=]\s*["\']?([^"\']+)["\']?/i', $text, $matches)) {
        $result['overall_assessment'] = trim($matches[1]);
    }
    
    // Try to extract risk_level
    if (preg_match('/risk[_\s]?level["\']?\s*[:=]\s*["\']?(low|moderate|high|critical)["\']?/i', $text, $matches)) {
        $result['risk_level'] = strtolower($matches[1]);
    }
    
    // Try to extract arrays (immediate_impacts, potential_hazards, etc.)
    $arrayFields = ['immediate_impacts', 'potential_hazards', 'recommendations', 'affected_areas'];
    foreach ($arrayFields as $field) {
        $pattern = '/' . preg_quote($field, '/') . '["\']?\s*[:=]\s*\[(.*?)\]/is';
        if (preg_match($pattern, $text, $matches)) {
            $items = [];
            // Extract quoted strings
            if (preg_match_all('/["\']([^"\']+)["\']/', $matches[1], $itemMatches)) {
                $items = $itemMatches[1];
            }
            if (!empty($items)) {
                $result[$field] = $items;
            }
        }
    }
    
    return !empty($result) ? $result : null;
}

/**
 * Calculate distance between two points using Haversine formula
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Earth's radius in kilometers
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

/**
 * Assess earthquake risk specifically for prediction and planning
 */
function assessEarthquakeRisk() {
    global $pdo;
    
    // Check if AI analysis is enabled for earthquake
    if (!isAIAnalysisEnabled('earthquake')) {
        ob_clean();
        http_response_code(403);
        $output = json_encode([
            'success' => false, 
            'message' => 'AI earthquake analysis is currently disabled.'
        ], JSON_UNESCAPED_UNICODE);
        echo $output;
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit();
    }
    
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['earthquakes'])) {
            throw new Exception('No earthquake data provided');
        }
        
        $earthquakes = $data['earthquakes'];
        $baseRisk = $data['base_risk'] ?? 'unknown';
        
        // Get API key
        $apiKey = getGeminiApiKey('analysis');
        if (empty($apiKey)) {
            $apiKey = getGeminiApiKey('default');
        }
        
        if (empty($apiKey)) {
            throw new Exception('API key not configured');
        }
        
        // Build prompt
        $prompt = buildRiskAssessmentPrompt($earthquakes, $baseRisk);
        
        // Call API
        $response = callGeminiAI($apiKey, $prompt);
        
        if ($response['success']) {
            ob_clean();
            echo json_encode([
                'success' => true,
                'analysis' => $response['content'],
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception($response['error'] ?? 'AI processing failed');
        }
        
    } catch (Exception $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit();
}

/**
 * Build prompt for risk assessment and prediction
 */
function buildRiskAssessmentPrompt($earthquakes, $baseRisk) {
    // Summarize earthquake data for the prompt
    $count = count($earthquakes);
    $qcLat = 14.6488;
    $qcLon = 121.0509;
    
    $prompt = "Act as an expert Seismic Risk Analyst for the Quezon City Local Government Unit (LGU).\n";
    $prompt .= "Analyze the following recent earthquake data (last 30 days) relative to Quezon City (Lat: $qcLat, Lon: $qcLon).\n";
    $prompt .= "The calculated base risk level based on distance/magnitude is: " . strtoupper($baseRisk) . ".\n\n";
    
    $prompt .= "Recent Seismic Activity ($count events):\n";
    // Limit to top 10 most relevant for brevity in prompt
    $relevant = array_slice($earthquakes, 0, 10);
    foreach ($relevant as $eq) {
        $prompt .= "- Mag {$eq['magnitude']} | Depth {$eq['depth']}km | Dist {$eq['distanceFromQC']}km\n";
    }
    
    $prompt .= "\nBased on these patterns, generate a structured Risk Analysis & Prediction Report specifically for Quezon City.\n";
    $prompt .= "Output MUST be valid JSON with the following fields:\n";
    $prompt .= "{\n";
    $prompt .= "  \"ai_risk_level\": \"Low | Moderate | High\",\n";
    $prompt .= "  \"risk_summary\": \"Concise summary of the current seismic threat level.\",\n";
    $prompt .= "  \"prediction\": \"Short-term prediction (7-14 days) with confidence level (Low/Medium/High). Analyze trends (swarms, aftershocks, increasing intensity).\",\n";
    $prompt .= "  \"impact_analysis\": \"Potential effects on Quezon City's people, infrastructure (bridges, tall buildings), and utilities if activity escalates.\",\n";
    $prompt .= "  \"recommendations\": \"Specific, actionable preparedness and mitigation steps for the LGU.\"\n";
    $prompt .= "}\n";
    $prompt .= "Tone: Official, authoritative, forecast-oriented. No markdown formatting, just raw JSON.";
    
    return $prompt;
}

