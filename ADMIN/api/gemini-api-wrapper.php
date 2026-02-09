<?php
/**
 * Gemini API Wrapper with Auto-Rotation
 * Automatically handles quota exceeded errors and rotates to backup keys
 */

require_once __DIR__ . '/secure-api-config.php';

/**
 * Call Gemini API with automatic key rotation on quota exceeded
 * 
 * @param string $prompt The prompt to send to Gemini
 * @param string $purpose The purpose of the API call ('analysis', 'translation', 'earthquake', 'default')
 * @param string $model The Gemini model to use
 * @param array $options Additional options for the API call
 * @return array Response with 'success', 'data', and 'error' keys
 */
function callGeminiWithAutoRotation($prompt, $purpose = 'default', $model = 'gemini-2.0-flash-exp', $options = []) {
    $maxAttempts = 3; // Try original + 2 backup attempts
    $attempt = 0;
    $lastError = '';
    $currentPurpose = $purpose;
    
    while ($attempt < $maxAttempts) {
        $attempt++;
        
        // Get API key for current attempt
        if ($attempt === 1) {
            $apiKey = getGeminiApiKey($currentPurpose);
            $keyName = purposeToKeyName($currentPurpose);
        } elseif ($attempt === 2 && $currentPurpose === 'analysis') {
            // Try backup key
            $apiKey = getGeminiApiKey('analysis_backup');
            $keyName = 'AI_API_KEY_ANALYSIS_BACKUP';
        } else {
            // Try general fallback
            $apiKey = getGeminiApiKey('default');
            $keyName = 'AI_API_KEY';
        }
        
        if (empty($apiKey)) {
            $lastError = "No API key available (attempt $attempt)";
            error_log($lastError);
            continue;
        }
        
        // Make API call
        $result = callGeminiApi($apiKey, $prompt, $model, $options);
        
        if ($result['success']) {
            return $result;
        }
        
        // Check if error is quota exceeded
        // "overloaded" is Google's way of saying the free tier is rate-limited
        $isQuotaError = false;
        if (isset($result['error'])) {
            $errorLower = strtolower($result['error']);
            $isQuotaError = (
                strpos($errorLower, 'quota') !== false ||
                strpos($errorLower, 'resource_exhausted') !== false ||
                strpos($errorLower, 'rate limit') !== false ||
                strpos($errorLower, 'overloaded') !== false ||
                strpos($errorLower, 'exceeded') !== false ||
                strpos($errorLower, '429') !== false
            );
        }
        
        if ($isQuotaError) {
            error_log("⚠️ Quota exceeded for $keyName on attempt $attempt");
            
            // Trigger auto-rotation
            $backupKey = rotateApiKeyOnQuotaExceeded($keyName, $result['error']);
            
            if ($backupKey) {
                error_log("✅ Rotated to backup key, will retry");
                $lastError = $result['error'];
                continue; // Retry with next key
            } else {
                error_log("❌ Auto-rotation failed or not configured");
                $lastError = "Quota exceeded and auto-rotation unavailable: " . $result['error'];
                break;
            }
        } else {
            // Non-quota error, don't retry
            return $result;
        }
    }
    
    return [
        'success' => false,
        'error' => "Failed after $attempt attempts. Last error: $lastError",
        'attempts' => $attempt
    ];
}

/**
 * Make direct API call to Gemini
 */
function callGeminiApi($apiKey, $prompt, $model = 'gemini-2.0-flash-exp', $options = []) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    
    $payload = [
        'contents' => [
            ['parts' => [['text' => $prompt]]]
        ]
    ];
    
    // Merge additional options
    if (!empty($options['temperature'])) {
        $payload['generationConfig']['temperature'] = $options['temperature'];
    }
    if (!empty($options['maxOutputTokens'])) {
        $payload['generationConfig']['maxOutputTokens'] = $options['maxOutputTokens'];
    }
    if (!empty($options['topK'])) {
        $payload['generationConfig']['topK'] = $options['topK'];
    }
    if (!empty($options['topP'])) {
        $payload['generationConfig']['topP'] = $options['topP'];
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout'] ?? 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return [
            'success' => false,
            'error' => "CURL Error: $curlError"
        ];
    }
    
    if ($httpCode !== 200) {
        $errorMessage = "HTTP $httpCode";
        if ($response) {
            $responseData = json_decode($response, true);
            if (isset($responseData['error']['message'])) {
                $errorMessage = $responseData['error']['message'];
            }
        }
        
        return [
            'success' => false,
            'error' => $errorMessage,
            'http_code' => $httpCode,
            'raw_response' => $response
        ];
    }
    
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => 'Invalid JSON response from API'
        ];
    }
    
    // Extract text from response
    $text = '';
    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $responseData['candidates'][0]['content']['parts'][0]['text'];
    }
    
    return [
        'success' => true,
        'data' => $text,
        'full_response' => $responseData
    ];
}

/**
 * Convert purpose to key name
 */
function purposeToKeyName($purpose) {
    $map = [
        'earthquake' => 'AI_API_KEY_EARTHQUAKE',
        'analysis' => 'AI_API_KEY_ANALYSIS',
        'ai_message' => 'AI_API_KEY_AI_MESSAGE',
        'analysis_backup' => 'AI_API_KEY_ANALYSIS_BACKUP',
        'translation' => 'AI_API_KEY_TRANSLATION',
        'default' => 'AI_API_KEY'
    ];
    
    return $map[$purpose] ?? 'AI_API_KEY';
}

/**
 * Example usage function for testing
 */
function testGeminiApiWithRotation() {
    $prompt = "Say 'Hello from Gemini!' if you can read this.";
    $result = callGeminiWithAutoRotation($prompt, 'analysis');
    
    if ($result['success']) {
        echo "✅ Success: " . $result['data'] . "\n";
    } else {
        echo "❌ Error: " . $result['error'] . "\n";
    }
    
    return $result;
}

