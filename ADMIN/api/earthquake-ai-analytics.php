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
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Fatal error: " . $error['message'] . " in " . basename($error['file']) . " on line " . $error['line']
        ]);
        error_log("Earthquake AI Analytics Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
    }
});

header('Content-Type: application/json; charset=utf-8');

try {
    require_once 'db_connect.php';
    require_once 'secure-api-config.php';
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Configuration error: ' . $e->getMessage()]);
    error_log('Earthquake AI Analytics: Failed to load required files: ' . $e->getMessage());
    exit();
} catch (Error $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fatal error loading files: ' . $e->getMessage()]);
    error_log('Earthquake AI Analytics: Fatal error loading files: ' . $e->getMessage());
    exit();
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? 'analyze';

try {
    switch ($action) {
        case 'analyze':
            analyzeEarthquakeImpact();
            break;
        default:
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    $errorMessage = 'Error: ' . $e->getMessage();
    error_log('Earthquake AI Analytics Exception: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => $errorMessage]);
} catch (Error $e) {
    ob_end_clean();
    http_response_code(500);
    $errorMessage = 'Fatal error: ' . $e->getMessage();
    error_log('Earthquake AI Analytics Fatal Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => $errorMessage]);
}

/**
 * Analyze earthquake impact on Quezon City
 */
function analyzeEarthquakeImpact() {
    global $pdo;
    
    try {
        // Get earthquake data from request
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No data received']);
            exit();
        }
        
        $earthquakeData = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
            exit();
        }
        
        if (empty($earthquakeData) || !isset($earthquakeData['earthquakes'])) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No earthquake data provided']);
            exit();
        }
        
        $earthquakes = $earthquakeData['earthquakes'] ?? [];
        
        if (empty($earthquakes) || !is_array($earthquakes)) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Empty or invalid earthquake array']);
            exit();
        }
        
        // Get API key for analysis
        $apiKey = null;
        try {
            $apiKey = getGeminiApiKey('analysis');
            if (empty($apiKey)) {
                $apiKey = getGeminiApiKey('default');
            }
        } catch (Exception $e) {
            error_log('Error getting Gemini API key: ' . $e->getMessage());
        } catch (Error $e) {
            error_log('Fatal error getting Gemini API key: ' . $e->getMessage());
        }
        
        if (empty($apiKey)) {
            ob_end_clean();
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'AI API key not configured. Please configure Gemini API key in Automated Warnings → AI Warning Settings.'
            ]);
            exit();
        }
    } catch (Exception $e) {
        ob_end_clean();
        http_response_code(500);
        error_log('Error in analyzeEarthquakeImpact (input processing): ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error processing request: ' . $e->getMessage()]);
        exit();
    } catch (Error $e) {
        ob_end_clean();
        http_response_code(500);
        error_log('Fatal error in analyzeEarthquakeImpact (input processing): ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Fatal error processing request: ' . $e->getMessage()]);
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
        
        // Sort by magnitude (highest first) and limit to top 10 most significant
        usort($earthquakeSummary, function($a, $b) {
            return $b['magnitude'] <=> $a['magnitude'];
        });
        $earthquakeSummary = array_slice($earthquakeSummary, 0, 10);
        
        // Build AI prompt
        $prompt = buildAnalysisPrompt($earthquakeSummary, $quezonCityLat, $quezonCityLon);
        
        // Call Gemini AI
        $analysis = callGeminiAI($apiKey, $prompt);
        
        if ($analysis['success']) {
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'analysis' => $analysis['content'],
                'earthquakes_analyzed' => count($earthquakeSummary),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit();
        } else {
            ob_end_clean();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $analysis['error'] ?? 'Failed to generate analysis'
            ]);
            exit();
        }
    } catch (Exception $e) {
        ob_end_clean();
        http_response_code(500);
        error_log('Error in analyzeEarthquakeImpact (analysis): ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'Error during analysis: ' . $e->getMessage()]);
        exit();
    } catch (Error $e) {
        ob_end_clean();
        http_response_code(500);
        error_log('Fatal error in analyzeEarthquakeImpact (analysis): ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'Fatal error during analysis: ' . $e->getMessage()]);
        exit();
    }
}

/**
 * Build AI analysis prompt
 */
function buildAnalysisPrompt($earthquakes, $qcLat, $qcLon) {
    $prompt = "You are an expert seismologist and disaster risk assessment specialist analyzing earthquake impacts on Quezon City, Philippines.\n\n";
    $prompt .= "Quezon City Coordinates: Latitude {$qcLat}, Longitude {$qcLon}\n\n";
    $prompt .= "Recent Earthquakes in the Philippines Region:\n";
    
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
    
    $prompt .= "\nPlease provide a comprehensive analysis in JSON format with the following structure:\n";
    $prompt .= "{\n";
    $prompt .= "  \"overall_assessment\": \"Brief overall risk assessment for Quezon City\",\n";
    $prompt .= "  \"immediate_impacts\": [\"List of immediate impacts if any\"],\n";
    $prompt .= "  \"potential_hazards\": [\"List of potential hazards (ground shaking, liquefaction, landslides, tsunamis, etc.)\"],\n";
    $prompt .= "  \"risk_level\": \"low|moderate|high|critical\",\n";
    $prompt .= "  \"recommendations\": [\"List of recommended actions for Quezon City residents and authorities\"],\n";
    $prompt .= "  \"affected_areas\": [\"Specific areas in Quezon City that may be most affected\"],\n";
    $prompt .= "  \"magnitude_threshold\": \"Note if any earthquakes exceed dangerous thresholds\",\n";
    $prompt .= "  \"distance_analysis\": \"Analysis of how distance affects impact\"\n";
    $prompt .= "}\n\n";
    $prompt .= "Consider:\n";
    $prompt .= "- Distance decay: Earthquakes closer to Quezon City have more impact\n";
    $prompt .= "- Magnitude: Higher magnitude earthquakes can be felt from greater distances\n";
    $prompt .= "- Depth: Shallow earthquakes cause more surface shaking\n";
    $prompt .= "- Local geology: Quezon City's soil conditions and building structures\n";
    $prompt .= "- Historical patterns: Known fault lines and seismic activity in the region\n";
    $prompt .= "- Secondary hazards: Liquefaction risk, landslides, building damage potential\n\n";
    $prompt .= "Provide accurate, scientific, and actionable analysis. Return ONLY valid JSON, no additional text.";
    
    return $prompt;
}

/**
 * Call Gemini AI API
 */
function callGeminiAI($apiKey, $prompt) {
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
                $lastError = "HTTP Error {$httpCode}: " . $errorMessage;
                error_log("Gemini API HTTP error ({$version}): {$httpCode} - " . $errorMessage);
                continue;
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

