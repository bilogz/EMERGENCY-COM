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

// Get API key securely from config file (not from client)
$apiKey = getGeminiApiKey();
if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'API key expired or invalid. Please update your Gemini API key in Automated Warnings → AI Warning Settings.'
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
    
    // Check for specific error types
    if (strpos(strtolower($errorMsg), 'expired') !== false || strpos(strtolower($errorMsg), 'invalid') !== false) {
        $errorMsg = 'API key expired or invalid. Please renew the API key in AI Warning Settings.';
    }
    
    echo json_encode([
        'success' => false, 
        'message' => 'API Error: ' . $errorMsg,
        'error_code' => $httpCode,
        'error_details' => $errorData
    ]);
    exit();
}

$responseData = json_decode($response, true);

if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    $aiResponse = $responseData['candidates'][0]['content']['parts'][0]['text'];
    echo json_encode([
        'success' => true,
        'response' => $aiResponse
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No response from AI']);
}

