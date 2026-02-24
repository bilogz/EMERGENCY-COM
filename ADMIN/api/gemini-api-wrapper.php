<?php
/**
 * Gemini API Wrapper with Auto-Rotation
 * Automatically handles quota exceeded errors and rotates to backup keys.
 */

require_once __DIR__ . '/secure-api-config.php';

/**
 * Build key fallback order by purpose.
 */
function geminiPurposeFallbackOrder($purpose) {
    $purpose = strtolower(trim((string)$purpose));
    $map = [
        'analysis' => ['analysis', 'analysis_backup', 'default'],
        'analysis_backup' => ['analysis_backup', 'analysis', 'default'],
        'ai_message' => ['ai_message', 'default', 'analysis', 'analysis_backup'],
        'translation' => ['translation', 'translation_backup', 'default'],
        'translation_backup' => ['translation_backup', 'translation', 'default'],
        'earthquake' => ['earthquake', 'analysis', 'analysis_backup', 'default'],
        'default' => ['default', 'analysis', 'analysis_backup']
    ];

    $order = $map[$purpose] ?? $map['default'];
    return array_values(array_unique(array_filter($order)));
}

/**
 * Call Gemini API with automatic key rotation and fallback.
 *
 * @param string $prompt The prompt to send to Gemini
 * @param string $purpose The purpose of the API call ('analysis', 'translation', 'earthquake', 'ai_message', 'default')
 * @param string $model The Gemini model to use
 * @param array $options Additional options for the API call
 * @return array Response with 'success', 'data', and 'error' keys
 */
function callGeminiWithAutoRotation($prompt, $purpose = 'default', $model = 'gemini-2.0-flash-exp', $options = []) {
    $purposeSequence = geminiPurposeFallbackOrder($purpose);
    $attempt = 0;
    $lastError = '';
    $lastResult = null;

    foreach ($purposeSequence as $currentPurpose) {
        $attempt++;

        $apiKey = getGeminiApiKey($currentPurpose);
        $keyName = purposeToKeyName($currentPurpose);

        if (empty($apiKey)) {
            $lastError = "No API key available for {$keyName} (attempt {$attempt})";
            error_log($lastError);
            continue;
        }

        $result = callGeminiApi($apiKey, $prompt, $model, $options);
        $lastResult = $result;

        if (!empty($result['success'])) {
            $result['attempts'] = $attempt;
            $result['purpose_used'] = $currentPurpose;
            $result['key_name'] = $keyName;
            return $result;
        }

        $errorLower = strtolower((string)($result['error'] ?? ''));
        $isQuotaError = (
            strpos($errorLower, 'quota') !== false ||
            strpos($errorLower, 'resource_exhausted') !== false ||
            strpos($errorLower, 'rate limit') !== false ||
            strpos($errorLower, 'too many requests') !== false ||
            strpos($errorLower, 'overloaded') !== false ||
            strpos($errorLower, 'exceeded') !== false ||
            strpos($errorLower, '429') !== false
        );

        if ($isQuotaError) {
            error_log("Quota exceeded for {$keyName} on attempt {$attempt}");
            // Record rotation metrics when configured, then continue fallback attempts.
            rotateApiKeyOnQuotaExceeded($keyName, (string)($result['error'] ?? 'quota exceeded'));
            $lastError = "Quota exceeded for {$keyName}: " . (string)($result['error'] ?? 'Unknown quota error');
            continue;
        }

        $isKeyOrAuthError = (
            strpos($errorLower, 'api key') !== false ||
            strpos($errorLower, 'permission denied') !== false ||
            strpos($errorLower, 'permission_denied') !== false ||
            strpos($errorLower, 'unauthenticated') !== false ||
            strpos($errorLower, 'forbidden') !== false ||
            strpos($errorLower, '401') !== false ||
            strpos($errorLower, '403') !== false
        );

        // Keep trying fallback keys when auth/key problems are key-specific.
        if ($isKeyOrAuthError) {
            $lastError = "API key/auth error for {$keyName}: " . (string)($result['error'] ?? 'Unknown auth error');
            continue;
        }

        // Request-level issues are usually not solved by changing keys.
        return $result;
    }

    if ($lastError === '' && is_array($lastResult) && !empty($lastResult['error'])) {
        $lastError = (string)$lastResult['error'];
    }
    if ($lastError === '') {
        $lastError = 'No usable Gemini API key or request failed without detailed error.';
    }

    return [
        'success' => false,
        'error' => "Failed after {$attempt} attempts. Last error: {$lastError}",
        'attempts' => $attempt
    ];
}

/**
 * Make direct API call to Gemini.
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

    // Extract text from response (concatenate all text parts to avoid truncated replies).
    $text = '';
    $finishReason = null;
    if (isset($responseData['candidates'][0]) && is_array($responseData['candidates'][0])) {
        $finishReason = $responseData['candidates'][0]['finishReason'] ?? null;
        $parts = $responseData['candidates'][0]['content']['parts'] ?? [];
        if (is_array($parts)) {
            $chunks = [];
            foreach ($parts as $part) {
                if (is_array($part) && isset($part['text']) && is_string($part['text']) && $part['text'] !== '') {
                    $chunks[] = $part['text'];
                }
            }
            if (!empty($chunks)) {
                $text = implode('', $chunks);
            }
        }
    }

    // Fallback: scan other candidates if first candidate has no text.
    if ($text === '' && isset($responseData['candidates']) && is_array($responseData['candidates'])) {
        foreach ($responseData['candidates'] as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            $parts = $candidate['content']['parts'] ?? [];
            if (!is_array($parts)) {
                continue;
            }
            $chunks = [];
            foreach ($parts as $part) {
                if (is_array($part) && isset($part['text']) && is_string($part['text']) && $part['text'] !== '') {
                    $chunks[] = $part['text'];
                }
            }
            if (!empty($chunks)) {
                $text = implode('', $chunks);
                $finishReason = $candidate['finishReason'] ?? $finishReason;
                break;
            }
        }
    }

    return [
        'success' => true,
        'data' => $text,
        'finish_reason' => $finishReason,
        'full_response' => $responseData
    ];
}

/**
 * Convert purpose to key name.
 */
function purposeToKeyName($purpose) {
    $map = [
        'earthquake' => 'AI_API_KEY_EARTHQUAKE',
        'analysis' => 'AI_API_KEY_ANALYSIS',
        'ai_message' => 'AI_API_KEY_AI_MESSAGE',
        'analysis_backup' => 'AI_API_KEY_ANALYSIS_BACKUP',
        'translation' => 'AI_API_KEY_TRANSLATION',
        'translation_backup' => 'AI_API_KEY_TRANSLATION_BACKUP',
        'default' => 'AI_API_KEY'
    ];

    return $map[$purpose] ?? 'AI_API_KEY';
}

/**
 * Example usage function for testing.
 */
function testGeminiApiWithRotation() {
    $prompt = "Say 'Hello from Gemini!' if you can read this.";
    $result = callGeminiWithAutoRotation($prompt, 'analysis');

    if ($result['success']) {
        echo "Success: " . $result['data'] . "\n";
    } else {
        echo "Error: " . $result['error'] . "\n";
    }

    return $result;
}
