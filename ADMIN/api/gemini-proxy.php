<?php
/**
 * Gemini AI API Proxy
 * Handles weather AI analysis calls through server-side keys.
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

require_once __DIR__ . '/secure-api-config.php';
require_once __DIR__ . '/db_connect.php';

if (!isAIAnalysisEnabled('weather')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'AI weather analysis is disabled. Enable it in General Settings -> AI Analysis Settings.'
    ]);
    exit();
}

/**
 * Execute one Gemini request.
 */
function sendGeminiRequest(string $apiKey, string $model, string $version, array $payload): array
{
    $url = "https://generativelanguage.googleapis.com/{$version}/models/{$model}:generateContent?key=" . urlencode($apiKey);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return [
            'success' => false,
            'http_code' => 500,
            'error_message' => 'CURL Error: ' . $curlError,
            'error_reason' => 'curl_error',
            'response_data' => null
        ];
    }

    $responseData = json_decode((string)$response, true);
    $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if ($httpCode === 200 && is_string($text) && $text !== '') {
        return [
            'success' => true,
            'http_code' => 200,
            'response_text' => $text,
            'response_data' => $responseData
        ];
    }

    return [
        'success' => false,
        'http_code' => (int)$httpCode,
        'error_message' => (string)($responseData['error']['message'] ?? 'Unknown error'),
        'error_reason' => (string)($responseData['error']['reason'] ?? ''),
        'response_data' => $responseData
    ];
}

function isQuotaError(int $httpCode, string $message, string $reason): bool
{
    $combined = strtolower($message . ' ' . $reason);
    return $httpCode === 429
        || strpos($combined, 'quota') !== false
        || strpos($combined, 'resource_exhausted') !== false
        || strpos($combined, 'rate limit') !== false
        || strpos($combined, 'too many requests') !== false
        || strpos($combined, 'overloaded') !== false;
}

function isModelError(int $httpCode, string $message, string $reason): bool
{
    $combined = strtolower($message . ' ' . $reason);
    return $httpCode === 404
        || strpos($combined, 'model') !== false
        || strpos($combined, 'not_found') !== false
        || strpos($combined, 'not found') !== false;
}

$apiKey = getGeminiApiKey('analysis');
if (empty($apiKey)) {
    $apiKey = getGeminiApiKey('default');
}

if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'API key not found. Configure Gemini keys in Automated Warnings.'
    ]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || !isset($input['prompt']) || trim((string)$input['prompt']) === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing prompt']);
    exit();
}

$prompt = trim((string)$input['prompt']);
$model = trim((string)getGeminiModel());
if ($model === '') {
    $model = 'gemini-1.5-flash';
}

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 2048,
        'topP' => 0.95,
        'topK' => 40
    ]
];

$versions = ['v1', 'v1beta'];
$lastResult = [
    'success' => false,
    'http_code' => 500,
    'error_message' => 'Unknown error',
    'error_reason' => ''
];
$lastVersion = 'v1';

// 1) Try configured model with primary key
foreach ($versions as $version) {
    $result = sendGeminiRequest($apiKey, $model, $version, $payload);
    if ($result['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'response' => $result['response_text'],
            'model_used' => $model,
            'api_version' => $version
        ]);
        exit();
    }
    $lastResult = $result;
    $lastVersion = $version;
}

$httpCode = (int)($lastResult['http_code'] ?? 500);
$errorMsg = (string)($lastResult['error_message'] ?? 'Unknown error');
$errorReason = (string)($lastResult['error_reason'] ?? '');

// 2) Quota handling first: try backup keys before model fallback
$retryKeys = [
    getGeminiApiKey('analysis_backup'),
    getGeminiApiKey('default')
];
$retryKeys = array_values(array_unique(array_filter($retryKeys, function ($key) use ($apiKey) {
    return !empty($key) && $key !== $apiKey;
})));

if (isQuotaError($httpCode, $errorMsg, $errorReason)) {
    foreach ($retryKeys as $retryKey) {
        foreach ($versions as $version) {
            $result = sendGeminiRequest($retryKey, $model, $version, $payload);
            if ($result['success']) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'response' => $result['response_text'],
                    'model_used' => $model,
                    'api_version' => $version,
                    'key_rotated' => true
                ]);
                exit();
            }
            $lastResult = $result;
            $lastVersion = $version;
        }
    }

    $httpCode = (int)($lastResult['http_code'] ?? 429);
    $errorMsg = (string)($lastResult['error_message'] ?? $errorMsg);
    $errorReason = (string)($lastResult['error_reason'] ?? $errorReason);
}

// 3) Model fallback after quota handling
if (isModelError($httpCode, $errorMsg, $errorReason)) {
    $fallbackModels = [
        'gemini-1.5-flash',
        'gemini-1.5-pro',
        'gemini-2.0-flash-exp',
        'gemini-pro'
    ];

    $allKeys = array_values(array_unique(array_filter(array_merge([$apiKey], $retryKeys))));

    foreach ($fallbackModels as $fallbackModel) {
        if ($fallbackModel === $model) {
            continue;
        }
        foreach ($allKeys as $key) {
            foreach ($versions as $version) {
                $result = sendGeminiRequest($key, $fallbackModel, $version, $payload);
                if ($result['success']) {
                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'response' => $result['response_text'],
                        'model_used' => $fallbackModel,
                        'api_version' => $version,
                        'original_model' => $model
                    ]);
                    exit();
                }
                $lastResult = $result;
                $lastVersion = $version;
            }
        }
    }

    $httpCode = (int)($lastResult['http_code'] ?? 404);
    $errorMsg = (string)($lastResult['error_message'] ?? $errorMsg);
    $errorReason = (string)($lastResult['error_reason'] ?? $errorReason);
}

// Final error normalization
if (isQuotaError($httpCode, $errorMsg, $errorReason)) {
    $httpCode = 429;
    $errorMsg = 'Rate limit reached for AI analysis. Please retry in 30-60 seconds.';
} elseif (
    strpos(strtolower($errorMsg), 'expired') !== false ||
    strpos(strtolower($errorMsg), 'invalid') !== false ||
    strpos(strtolower($errorReason), 'api_key') !== false ||
    $httpCode === 400 || $httpCode === 401 || $httpCode === 403
) {
    $errorMsg = 'API key expired or invalid. Update Gemini keys in Automated Warnings.';
} elseif (isModelError($httpCode, $errorMsg, $errorReason)) {
    $errorMsg = 'Model unavailable. Configure a supported Gemini model. Tried: ' . $model;
}

http_response_code($httpCode > 0 ? $httpCode : 500);
echo json_encode([
    'success' => false,
    'message' => 'API Error: ' . $errorMsg,
    'error_code' => $httpCode,
    'error_reason' => $errorReason,
    'error_details' => $lastResult['response_data'] ?? null,
    'debug_info' => [
        'model' => $model,
        'api_version_last' => $lastVersion
    ]
]);
exit();
