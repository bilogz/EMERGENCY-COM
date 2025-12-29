<?php
/**
 * Direct Gemini API Test
 * Tests the API key and model directly
 */

header('Content-Type: text/plain; charset=utf-8');

// Load config
$configFile = __DIR__ . '/config.local.php';
if (!file_exists($configFile)) {
    die("ERROR: config.local.php not found!");
}

$config = require $configFile;

$apiKey = $config['AI_API_KEY_TRANSLATION'] ?? $config['AI_API_KEY'] ?? '';
$model = $config['GEMINI_MODEL'] ?? 'gemini-2.5-flash';

echo "=== Gemini API Direct Test ===\n\n";
echo "API Key (first 15 chars): " . substr($apiKey, 0, 15) . "...\n";
echo "Model: $model\n\n";

if (empty($apiKey) || strpos($apiKey, 'YOUR_') !== false) {
    die("ERROR: API key not configured properly!\n");
}

// Test text
$testText = "Download Our Mobile App";
$targetLang = "Chinese";

echo "Test Text: $testText\n";
echo "Target Language: $targetLang\n\n";

// Try different model name formats
$modelVariants = [
    'gemini-2.5-flash',
    'gemini-2.5-flash-preview-05-20',
    'gemini-1.5-flash',
    'gemini-1.5-flash-latest',
];

foreach ($modelVariants as $testModel) {
    echo "--- Testing model: $testModel ---\n";
    
    $prompt = "Translate this text to $targetLang. Return ONLY the translation, no explanations:\n\n$testText";
    
    $data = [
        'contents' => [
            ['parts' => [['text' => $prompt]]]
        ],
        'generationConfig' => [
            'temperature' => 0.3,
            'maxOutputTokens' => 500,
        ]
    ];
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$testModel}:generateContent?key={$apiKey}";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    
    if ($curlError) {
        echo "CURL Error: $curlError\n";
    }
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $translated = trim($result['candidates'][0]['content']['parts'][0]['text']);
            echo "✅ SUCCESS! Translated: $translated\n";
            echo "\n=== WORKING MODEL FOUND: $testModel ===\n";
            echo "Update your config.local.php with: 'GEMINI_MODEL' => '$testModel'\n";
            break;
        } else {
            echo "Response structure unexpected\n";
            echo "Response: " . substr($response, 0, 500) . "\n";
        }
    } else {
        $errorData = json_decode($response, true);
        $errorMsg = $errorData['error']['message'] ?? 'Unknown error';
        echo "❌ FAILED: $errorMsg\n";
    }
    
    echo "\n";
}

echo "\n=== Test Complete ===\n";
?>

