<?php
/**
 * Simple Gemini API Test
 * Test your Gemini API key and model configuration
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Gemini API Test</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;background:#1a1a1a;color:#fff;}";
echo ".success{background:#2d5a27;padding:15px;border-radius:5px;margin:10px 0;}";
echo ".error{background:#5a2727;padding:15px;border-radius:5px;margin:10px 0;}";
echo ".info{background:#2a2a3a;padding:15px;border-radius:5px;margin:10px 0;}";
echo "pre{background:#000;padding:10px;border-radius:5px;overflow-x:auto;}";
echo "h1{color:#8e44ad;}</style></head><body>";
echo "<h1>🔍 Gemini API Configuration Test</h1>";

require_once 'secure-api-config.php';

// Test 1: Check config file
echo "<div class='info'><h2>Step 1: Config File Check</h2>";
$configPath = __DIR__ . '/../../USERS/api/config.local.php';
if (file_exists($configPath)) {
    echo "✅ Config file found: <code>$configPath</code><br>";
    try {
        $config = require $configPath;
        if (is_array($config)) {
            echo "✅ Config file is valid PHP array<br>";
            if (isset($config['AI_API_KEY'])) {
                $keyPreview = substr($config['AI_API_KEY'], 0, 20) . '...';
                echo "✅ API Key found: <code>$keyPreview</code><br>";
            } else {
                echo "❌ API Key not found in config array<br>";
            }
            if (isset($config['GEMINI_MODEL'])) {
                echo "✅ Model configured: <code>{$config['GEMINI_MODEL']}</code><br>";
            } else {
                echo "⚠️ Model not set, will use default<br>";
            }
        } else {
            echo "❌ Config file is not a valid array<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error loading config: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
} else {
    echo "❌ Config file NOT found at: <code>$configPath</code><br>";
    echo "Please create this file with your API key.<br>";
}
echo "</div>";

// Test 2: Get API key
echo "<div class='info'><h2>Step 2: API Key Retrieval</h2>";
$apiKey = getGeminiApiKey();
if (!empty($apiKey)) {
    $keyPreview = substr($apiKey, 0, 20) . '...';
    echo "✅ API Key retrieved successfully: <code>$keyPreview</code><br>";
} else {
    echo "❌ Failed to retrieve API key<br>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Test 3: Get Model
echo "<div class='info'><h2>Step 3: Model Configuration</h2>";
$model = getGeminiModel();
echo "✅ Model: <code>$model</code><br>";
echo "</div>";

// Test 4: Test API Call
echo "<div class='info'><h2>Step 4: API Connection Test</h2>";
echo "Testing connection to Gemini API...<br><br>";

$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

$testData = [
    'contents' => [
        [
            'parts' => [
                ['text' => 'Say "Hello" if you can read this.']
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 50
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "<div class='error'>";
    echo "❌ CURL Error: <code>" . htmlspecialchars($curlError) . "</code><br>";
    echo "This usually means a network connectivity issue.<br>";
    echo "</div>";
} elseif ($httpCode === 200) {
    $responseData = json_decode($response, true);
    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        $aiResponse = $responseData['candidates'][0]['content']['parts'][0]['text'];
        echo "<div class='success'>";
        echo "✅ <strong>SUCCESS! API is working!</strong><br><br>";
        echo "Model: <code>$model</code><br>";
        echo "Response: <code>" . htmlspecialchars($aiResponse) . "</code><br>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "⚠️ HTTP 200 but unexpected response format<br>";
        echo "<pre>" . htmlspecialchars(json_encode($responseData, JSON_PRETTY_PRINT)) . "</pre>";
        echo "</div>";
    }
} else {
    $errorData = json_decode($response, true);
    $errorMsg = $errorData['error']['message'] ?? 'Unknown error';
    $errorReason = $errorData['error']['reason'] ?? '';
    
    echo "<div class='error'>";
    echo "❌ <strong>API Error (HTTP $httpCode)</strong><br><br>";
    echo "Message: <code>" . htmlspecialchars($errorMsg) . "</code><br>";
    if ($errorReason) {
        echo "Reason: <code>" . htmlspecialchars($errorReason) . "</code><br>";
    }
    
    // Try fallback models
    if ($httpCode === 404 || strpos(strtolower($errorMsg), 'model') !== false) {
        echo "<br><strong>Trying alternative models...</strong><br>";
        $fallbackModels = ['gemini-1.5-flash', 'gemini-1.5-pro', 'gemini-pro'];
        
        foreach ($fallbackModels as $fallbackModel) {
            if ($fallbackModel === $model) continue;
            
            echo "Trying <code>$fallbackModel</code>... ";
            $fallbackUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$fallbackModel}:generateContent?key=" . urlencode($apiKey);
            
            $ch2 = curl_init($fallbackUrl);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_POST, true);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($testData));
            curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
            
            $fallbackResponse = curl_exec($ch2);
            $fallbackHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);
            
            if ($fallbackHttpCode === 200) {
                $fallbackData = json_decode($fallbackResponse, true);
                if (isset($fallbackData['candidates'][0]['content']['parts'][0]['text'])) {
                    echo "<span style='color:#2ecc71;'>✅ WORKS!</span><br>";
                    echo "✅ Use this model in your config: <code>$fallbackModel</code><br>";
                    break;
                }
            } else {
                echo "<span style='color:#e74c3c;'>❌ Failed</span><br>";
            }
        }
    }
    
    echo "<br><pre>" . htmlspecialchars(json_encode($errorData, JSON_PRETTY_PRINT)) . "</pre>";
    echo "</div>";
}

echo "</div>";

// Test 5: Test via proxy
echo "<div class='info'><h2>Step 5: Proxy Test</h2>";
echo "Testing the gemini-proxy.php endpoint...<br><br>";

$proxyUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/gemini-proxy.php';

$proxyData = json_encode(['prompt' => 'Say hello in one word']);

$ch3 = curl_init($proxyUrl);
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch3, CURLOPT_POST, true);
curl_setopt($ch3, CURLOPT_POSTFIELDS, $proxyData);
curl_setopt($ch3, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch3, CURLOPT_TIMEOUT, 15);

$proxyResponse = curl_exec($ch3);
$proxyHttpCode = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
curl_close($ch3);

if ($proxyHttpCode === 200) {
    $proxyResult = json_decode($proxyResponse, true);
    if (isset($proxyResult['success']) && $proxyResult['success']) {
        echo "<div class='success'>";
        echo "✅ Proxy endpoint is working!<br>";
        echo "Response: <code>" . htmlspecialchars(substr($proxyResult['response'], 0, 100)) . "...</code><br>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "⚠️ Proxy returned error: <code>" . htmlspecialchars($proxyResult['message'] ?? 'Unknown') . "</code><br>";
        echo "</div>";
    }
} else {
    echo "<div class='error'>";
    echo "❌ Proxy test failed (HTTP $proxyHttpCode)<br>";
    echo "Response: <pre>" . htmlspecialchars(substr($proxyResponse, 0, 500)) . "</pre>";
    echo "</div>";
}

echo "</div>";

echo "<hr style='border-color:#333;margin:30px 0;'>";
echo "<h2>📝 Summary</h2>";
echo "<p>If all tests passed, your Gemini API integration is ready to use!</p>";
echo "<p><a href='../sidebar/weather-monitoring.php' style='color:#8e44ad;'>→ Go to Weather Monitoring</a> to test the AI Weather Analysis feature.</p>";

echo "</body></html>";
?>

