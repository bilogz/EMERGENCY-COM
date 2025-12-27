<?php
/**
 * Gemini AI API Proxy
 * Handles CORS and API calls to Google Gemini
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

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['apiKey']) || !isset($input['prompt'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing apiKey or prompt']);
    exit();
}

$apiKey = $input['apiKey'];
$prompt = $input['prompt'];

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3-pro-preview:generateContent?key=" . urlencode($apiKey);

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
        'maxOutputTokens' => 2048, // Increased for gemini-3-pro-preview
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
    echo json_encode(['success' => false, 'message' => 'API Error: ' . $errorMsg]);
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

