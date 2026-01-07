<?php
/**
 * Earthquake AI Analytics API
 * Analyzes earthquake data and predicts impact on Quezon City
 * Uses Gemini AI to provide hazard assessments and impact predictions
 */

header('Content-Type: application/json; charset=utf-8');

require_once 'db_connect.php';
require_once 'secure-api-config.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
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
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

/**
 * Analyze earthquake impact on Quezon City
 */
function analyzeEarthquakeImpact() {
    global $pdo;
    
    // Get earthquake data from request
    $earthquakeData = json_decode(file_get_contents('php://input'), true);
    
    if (empty($earthquakeData) || !isset($earthquakeData['earthquakes'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No earthquake data provided']);
        return;
    }
    
    $earthquakes = $earthquakeData['earthquakes'] ?? [];
    
    if (empty($earthquakes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No earthquake data provided']);
        return;
    }
    
    // Get API key for analysis
    $apiKey = getGeminiApiKey('analysis');
    if (empty($apiKey)) {
        $apiKey = getGeminiApiKey('default');
    }
    
    if (empty($apiKey)) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'AI API key not configured. Please configure Gemini API key in Automated Warnings → AI Warning Settings.'
        ]);
        return;
    }
    
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
        echo json_encode([
            'success' => true,
            'analysis' => $analysis['content'],
            'earthquakes_analyzed' => count($earthquakeSummary),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $analysis['error'] ?? 'Failed to generate analysis'
        ]);
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
    $model = getGeminiModel();
    
    // Try v1 API first (for Gemini 2.5), fallback to v1beta
    $apiVersions = ['v1', 'v1beta'];
    $lastError = null;
    
    foreach ($apiVersions as $version) {
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
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            $lastError = "cURL Error: " . $curlError;
            continue;
        }
        
        if ($httpCode !== 200) {
            $lastError = "HTTP Error {$httpCode}: " . substr($response, 0, 200);
            continue;
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
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
        }
        
        $lastError = "Unexpected response format";
    }
    
    return [
        'success' => false,
        'error' => $lastError ?? 'Unknown error'
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

