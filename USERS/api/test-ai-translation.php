<?php
/**
 * Test AI Translation
 * Check if AI translation is working correctly
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'ai-translation-config.php';

$testText = $_GET['text'] ?? 'Download Our Mobile App';
$targetLang = $_GET['lang'] ?? 'zh';

echo json_encode([
    'test_info' => [
        'original_text' => $testText,
        'target_language' => $targetLang,
        'ai_provider' => AI_PROVIDER,
        'api_key_configured' => !empty(AI_API_KEY) && AI_API_KEY !== 'your-api-key-here',
        'api_key_preview' => substr(AI_API_KEY, 0, 10) . '...',
        'gemini_model' => defined('GEMINI_MODEL') ? GEMINI_MODEL : 'not set'
    ],
    'translation_result' => null,
    'raw_api_response' => null,
    'error' => null
], JSON_PRETTY_PRINT);

// Now test the actual translation
$targetName = getLanguageName($targetLang);

echo "\n\n--- Testing Translation ---\n";

// Direct Gemini API test
$model = defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-2.0-flash-exp';
$prompt = "Translate this text to $targetName. Return ONLY the translation, no explanations:\n\n$testText";

$data = [
    'contents' => [
        ['parts' => [['text' => $prompt]]]
    ],
    'generationConfig' => [
        'temperature' => 0.3,
        'topK' => 40,
        'topP' => 0.95,
        'maxOutputTokens' => 500,
    ]
];

$url = GEMINI_API_BASE . $model . ':generateContent?key=' . AI_API_KEY;

echo "\nAPI URL: " . preg_replace('/key=.*/', 'key=***', $url) . "\n";
echo "Model: $model\n";
echo "Prompt: $prompt\n\n";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($curlError) {
    echo "CURL Error: $curlError\n";
}

echo "\nRaw Response:\n";
echo $response . "\n\n";

if ($httpCode === 200 && $response) {
    $result = json_decode($response, true);
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $translatedText = trim($result['candidates'][0]['content']['parts'][0]['text']);
        echo "✅ TRANSLATED TEXT: $translatedText\n";
    } else {
        echo "❌ Could not extract translation from response\n";
        echo "Response structure: " . print_r($result, true) . "\n";
    }
} else {
    echo "❌ API call failed\n";
    if ($response) {
        $errorResult = json_decode($response, true);
        echo "Error details: " . print_r($errorResult, true) . "\n";
    }
}
?>

