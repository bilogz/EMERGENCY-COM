<?php
/**
 * Gemini AI API Proxy
 * Handles CORS and API calls to Google Gemini
 * SECURE: Uses API key from secure config, not from client
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Load secure API configuration
require_once __DIR__ . '/secure-api-config.php';
require_once __DIR__ . '/db_connect.php';

// Check if AI analysis is enabled for weather
if (!isAIAnalysisEnabled('weather')) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'AI weather analysis is currently disabled. Please enable it in General Settings → AI Analysis Settings to use this feature.'
    ]);
    exit();
}

// Get API key securely from config file (not from client)
$apiKey = getGeminiApiKey();
if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'API key not found. Please update your Gemini API key in Automated Warnings → AI Warning Settings.',
        'debug' => [
            'config_file_exists' => file_exists(__DIR__ . '/../../USERS/api/config.local.php'),
            'config_path' => __DIR__ . '/../../USERS/api/config.local.php'
        ]
    ]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['prompt'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing prompt']);
    exit();
}

$prompt = $input['prompt'];

// Get model from config (defaults to gemini-2.5-flash)
$model = getGeminiModel();

// Try v1 API first for newer models like 2.5, fallback to v1beta
$apiVersions = ['v1', 'v1beta'];
$usedVersion = 'v1'; // Start with v1 for Gemini 2.5
$url = "https://generativelanguage.googleapis.com/{$usedVersion}/models/{$model}:generateContent?key=" . urlencode($apiKey);

$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 2048, // Optimized for gemini-2.5-flash
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

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'CURL Error: ' . $curlError]);
    exit();
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    $errorData = json_decode($response, true);
    $errorMsg = $errorData['error']['message'] ?? 'Unknown error';
    $errorReason = $errorData['error']['reason'] ?? '';
    
    // Log the full error for debugging
    error_log("Gemini API Error - HTTP $httpCode: " . json_encode($errorData));
    error_log("API Key used: " . substr($apiKey, 0, 20) . "...");
    error_log("Model used: $model");
    
    // If model not found error, try different API versions and alternative models
    if ($httpCode === 404 || strpos(strtolower($errorMsg), 'model') !== false || strpos(strtolower($errorReason), 'not_found') !== false) {
        // First, try different API versions with the same model
        $apiVersions = ['v1', 'v1beta'];
        foreach ($apiVersions as $version) {
            if ($version === $usedVersion) continue; // Skip if already tried
            
            error_log("Trying API version: $version with model: $model");
            $versionUrl = "https://generativelanguage.googleapis.com/{$version}/models/{$model}:generateContent?key=" . urlencode($apiKey);
            
            $ch2 = curl_init($versionUrl);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_POST, true);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
            
            $versionResponse = curl_exec($ch2);
            $versionHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);
            
            if ($versionHttpCode === 200) {
                $responseData = json_decode($versionResponse, true);
                if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                    $aiResponse = $responseData['candidates'][0]['content']['parts'][0]['text'];
                    error_log("Successfully used API version: $version with model: $model");
                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'response' => $aiResponse,
                        'model_used' => $model,
                        'api_version' => $version
                    ]);
                    exit();
                }
            }
        }
        
        // Then try alternative models with both API versions
        $fallbackModels = [
            'gemini-2.5-flash',      // Try 2.5 directly
            'gemini-2.0-flash-exp',  // Experimental 2.0
            'gemini-1.5-flash',      // Standard 1.5 flash
            'gemini-1.5-pro',        // Pro version
            'gemini-pro'             // Legacy pro
        ];
        
        foreach ($fallbackModels as $fallbackModel) {
            if ($fallbackModel === $model) continue; // Skip if already tried
            
            foreach ($apiVersions as $version) {
                error_log("Trying fallback model: $fallbackModel with API version: $version");
                $fallbackUrl = "https://generativelanguage.googleapis.com/{$version}/models/{$fallbackModel}:generateContent?key=" . urlencode($apiKey);
                
                $ch2 = curl_init($fallbackUrl);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_POST, true);
                curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
                
                $fallbackResponse = curl_exec($ch2);
                $fallbackHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                curl_close($ch2);
                
                if ($fallbackHttpCode === 200) {
                    $responseData = json_decode($fallbackResponse, true);
                    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                        $aiResponse = $responseData['candidates'][0]['content']['parts'][0]['text'];
                        error_log("Successfully used fallback model: $fallbackModel with API version: $version");
                        http_response_code(200);
                        echo json_encode([
                            'success' => true,
                            'response' => $aiResponse,
                            'model_used' => $fallbackModel,
                            'api_version' => $version,
                            'original_model' => $model
                        ]);
                        exit();
                    }
                }
            }
        }
    }
    
    // Check for specific error types
    if (strpos(strtolower($errorMsg), 'expired') !== false || 
        strpos(strtolower($errorMsg), 'invalid') !== false ||
        strpos(strtolower($errorReason), 'api_key') !== false ||
        $httpCode === 400 || $httpCode === 401 || $httpCode === 403) {
        $errorMsg = 'API key expired or invalid. Please update your Gemini API key in Automated Warnings → AI Warning Settings.';
    } elseif (strpos(strtolower($errorMsg), 'model') !== false || $httpCode === 404) {
        $errorMsg = 'Model not found. Please check your model name in config. Tried: ' . $model;
    }
    
    echo json_encode([
        'success' => false, 
        'message' => 'API Error: ' . $errorMsg,
        'error_code' => $httpCode,
        'error_reason' => $errorReason,
        'error_details' => $errorData,
        'debug_info' => [
            'model' => $model,
            'api_key_prefix' => substr($apiKey, 0, 10) . '...'
        ]
    ]);
    exit();
}

$responseData = json_decode($response, true);

if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    $aiResponse = $responseData['candidates'][0]['content']['parts'][0]['text'];
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'response' => $aiResponse,
        'model_used' => $model,
        'api_version' => $usedVersion
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No response from AI']);
}

