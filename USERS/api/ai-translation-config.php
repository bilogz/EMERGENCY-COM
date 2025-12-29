<?php
/**
 * AI Translation Configuration
 * Simple config for AI-powered translations
 */

// ============================================
// AI API CONFIGURATION - SECURE LOADING
// ============================================

// Load secure config from local file (not in Git)
$secureConfig = [];
$configFile = __DIR__ . '/config.local.php';
if (file_exists($configFile)) {
    $secureConfig = require $configFile;
}

// Fallback to environment variables or defaults
$aiProvider = $secureConfig['AI_PROVIDER'] ?? $_ENV['AI_PROVIDER'] ?? 'gemini';
$aiApiKey = $secureConfig['AI_API_KEY'] ?? $_ENV['AI_API_KEY'] ?? '';
$aiApiKeyTranslation = $secureConfig['AI_API_KEY_TRANSLATION'] ?? $aiApiKey; // Use specific key for translation
$geminiModel = $secureConfig['GEMINI_MODEL'] ?? $_ENV['GEMINI_MODEL'] ?? 'gemini-2.5-flash';
$libreTranslateUrl = $secureConfig['LIBRETRANSLATE_URL'] ?? 'https://libretranslate.de/translate';
$libreTranslateKey = $secureConfig['LIBRETRANSLATE_API_KEY'] ?? '';

// Define constants
define('AI_PROVIDER', $aiProvider);
define('AI_API_KEY', $aiApiKey);
define('AI_API_KEY_TRANSLATION', $aiApiKeyTranslation);
define('GEMINI_MODEL', $geminiModel);
define('LIBRETRANSLATE_URL', $libreTranslateUrl);
define('LIBRETRANSLATE_API_KEY', $libreTranslateKey);

// API Endpoints
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
// Gemini API - Uses dynamic model from config
define('GEMINI_API_BASE', 'https://generativelanguage.googleapis.com/v1beta/models/');
define('CLAUDE_API_URL', 'https://api.anthropic.com/v1/messages');
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');

// Cache duration in days
define('TRANSLATION_CACHE_DAYS', 30);

// Language code mappings (for compatibility)
$LANG_MAP = [
    'fil' => 'Filipino',
    'tl' => 'Tagalog',
    'ceb' => 'Cebuano',
    'ilo' => 'Ilocano',
    'pam' => 'Kapampangan',
    'bcl' => 'Bicolano',
    'war' => 'Waray',
    'hil' => 'Hiligaynon',
    'es' => 'Spanish',
    'zh' => 'Chinese',
    'ja' => 'Japanese',
    'ko' => 'Korean',
    'ar' => 'Arabic',
    'hi' => 'Hindi',
    'ru' => 'Russian',
    'de' => 'German',
    'fr' => 'French',
    'it' => 'Italian',
    'pt' => 'Portuguese',
];

/**
 * Get language name from code
 */
function getLanguageName($code) {
    global $LANG_MAP;
    return $LANG_MAP[$code] ?? ucfirst($code);
}

/**
 * Translate text using AI
 */
function translateWithAI($text, $sourceLang, $targetLang) {
    $sourceName = getLanguageName($sourceLang);
    $targetName = getLanguageName($targetLang);
    
    if ($sourceLang === $targetLang) {
        return $text;
    }
    
    $provider = AI_PROVIDER;
    $apiKey = AI_API_KEY;
    
    if (empty($apiKey) || $apiKey === 'your-api-key-here') {
        error_log('AI API key not configured');
        return $text;
    }
    
    switch ($provider) {
        case 'openai':
            return translateWithOpenAI($text, $targetName, $apiKey);
        case 'gemini':
            return translateWithGemini($text, $targetName, $apiKey);
        case 'claude':
            return translateWithClaude($text, $targetName, $apiKey);
        case 'groq':
            return translateWithGroq($text, $targetName, $apiKey);
        default:
            return $text;
    }
}

/**
 * OpenAI Translation
 */
function translateWithOpenAI($text, $targetLang, $apiKey) {
    $prompt = "Translate this text to $targetLang. Return ONLY the translation, no explanations:\n\n$text";
    
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a professional translator. Translate accurately and naturally.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.3,
        'max_tokens' => 500
    ];
    
    $ch = curl_init(OPENAI_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 15
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        }
    }
    
    return $text;
}

/**
 * Google Gemini Translation (Gemini 2.5 Flash)
 */
function translateWithGemini($text, $targetLang, $apiKey) {
    $model = defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-2.0-flash-exp';
    $prompt = "Translate this text to $targetLang. Return ONLY the translation, no explanations:\n\n$text";
    
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
    
    $url = GEMINI_API_BASE . $model . ':generateContent?key=' . $apiKey;
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 15
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($result['candidates'][0]['content']['parts'][0]['text']);
        }
    }
    
    return $text;
}

/**
 * Claude Translation
 */
function translateWithClaude($text, $targetLang, $apiKey) {
    $prompt = "Translate this text to $targetLang. Return ONLY the translation:\n\n$text";
    
    $data = [
        'model' => 'claude-3-haiku-20240307',
        'max_tokens' => 500,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ];
    
    $ch = curl_init(CLAUDE_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01'
        ],
        CURLOPT_TIMEOUT => 15
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['content'][0]['text'])) {
            return trim($result['content'][0]['text']);
        }
    }
    
    return $text;
}

/**
 * Groq Translation (Fast & Free!)
 */
function translateWithGroq($text, $targetLang, $apiKey) {
    $prompt = "Translate this text to $targetLang. Return ONLY the translation:\n\n$text";
    
    $data = [
        'model' => 'llama3-8b-8192',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a professional translator.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.3,
        'max_tokens' => 500
    ];
    
    $ch = curl_init(GROQ_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 15
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        }
    }
    
    return $text;
}

/**
 * BATCH Translation - Translate multiple texts in ONE API call
 * This is MUCH faster than translating one by one!
 */
function translateBatchWithAI($textsArray, $sourceLang, $targetLang) {
    $targetName = getLanguageName($targetLang);
    $apiKey = defined('AI_API_KEY_TRANSLATION') ? AI_API_KEY_TRANSLATION : AI_API_KEY;
    
    if (empty($apiKey) || $apiKey === 'your-api-key-here') {
        error_log('AI API key not configured for batch translation');
        return $textsArray; // Return original texts
    }
    
    // Build the prompt with all texts
    $textList = "";
    $keys = array_keys($textsArray);
    foreach ($textsArray as $key => $text) {
        $textList .= "[$key]: $text\n";
    }
    
    $prompt = "Translate ALL the following texts to $targetName. 
Keep the exact same format with [key]: translation.
Return ONLY the translations, no explanations.

$textList";

    $provider = AI_PROVIDER;
    
    switch ($provider) {
        case 'gemini':
            return translateBatchWithGemini($textsArray, $keys, $prompt, $targetName, $apiKey);
        case 'openai':
            return translateBatchWithOpenAI($textsArray, $keys, $prompt, $targetName, $apiKey);
        case 'libretranslate':
            return translateBatchWithLibreTranslate($textsArray, $sourceLang, $targetLang);
        case 'mymemory':
            return translateBatchWithMyMemory($textsArray, $sourceLang, $targetLang);
        default:
            return translateBatchWithGemini($textsArray, $keys, $prompt, $targetName, $apiKey);
    }
}

/**
 * Batch translation with Gemini
 */
function translateBatchWithGemini($textsArray, $keys, $prompt, $targetLang, $apiKey) {
    $model = defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-2.5-flash';
    
    $data = [
        'contents' => [
            ['parts' => [['text' => $prompt]]]
        ],
        'generationConfig' => [
            'temperature' => 0.2,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 8000, // Increased for batch
        ]
    ];
    
    $url = GEMINI_API_BASE . $model . ':generateContent?key=' . $apiKey;
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 60 // Longer timeout for batch
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("Gemini batch translation CURL error: $curlError");
        return $textsArray;
    }
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $translatedText = $result['candidates'][0]['content']['parts'][0]['text'];
            return parseBatchTranslations($translatedText, $textsArray);
        }
    }
    
    error_log("Gemini batch translation failed. HTTP: $httpCode, Response: " . substr($response, 0, 500));
    return $textsArray; // Return original on failure
}

/**
 * Batch translation with OpenAI
 */
function translateBatchWithOpenAI($textsArray, $keys, $prompt, $targetLang, $apiKey) {
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a professional translator. Translate accurately and naturally. Keep the [key]: format exactly.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.2,
        'max_tokens' => 4000
    ];
    
    $ch = curl_init(OPENAI_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            $translatedText = $result['choices'][0]['message']['content'];
            return parseBatchTranslations($translatedText, $textsArray);
        }
    }
    
    return $textsArray;
}

/**
 * Parse batch translation response
 */
function parseBatchTranslations($responseText, $originalTexts) {
    $translations = [];
    $lines = explode("\n", $responseText);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Match [key]: translation format
        if (preg_match('/^\[([^\]]+)\]:\s*(.+)$/', $line, $matches)) {
            $key = $matches[1];
            $translation = trim($matches[2]);
            if (isset($originalTexts[$key])) {
                $translations[$key] = $translation;
            }
        }
    }
    
    // Fill in any missing translations with originals
    foreach ($originalTexts as $key => $original) {
        if (!isset($translations[$key])) {
            $translations[$key] = $original;
        }
    }
    
    return $translations;
}

// ============================================
// FAST TRANSLATION ALTERNATIVES (No AI)
// ============================================

/**
 * MyMemory API - FREE, no API key needed
 * Limit: 5000 chars/day (good for caching)
 */
function translateWithMyMemory($text, $sourceLang, $targetLang) {
    $url = 'https://api.mymemory.translated.net/get?' . http_build_query([
        'q' => $text,
        'langpair' => $sourceLang . '|' . $targetLang
    ]);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['responseData']['translatedText'])) {
            return $result['responseData']['translatedText'];
        }
    }
    
    return $text;
}

/**
 * Batch translation using MyMemory (parallel requests)
 */
function translateBatchWithMyMemory($textsArray, $sourceLang, $targetLang) {
    $translations = [];
    $mh = curl_multi_init();
    $handles = [];
    
    foreach ($textsArray as $key => $text) {
        $url = 'https://api.mymemory.translated.net/get?' . http_build_query([
            'q' => $text,
            'langpair' => $sourceLang . '|' . $targetLang
        ]);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15
        ]);
        
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }
    
    // Execute all requests in parallel
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);
    
    // Get results
    foreach ($handles as $key => $ch) {
        $response = curl_multi_getcontent($ch);
        $result = json_decode($response, true);
        
        if (isset($result['responseData']['translatedText'])) {
            $translations[$key] = $result['responseData']['translatedText'];
        } else {
            $translations[$key] = $textsArray[$key]; // Fallback to original
        }
        
        curl_multi_remove_handle($mh, $ch);
    }
    
    curl_multi_close($mh);
    
    return $translations;
}

/**
 * LibreTranslate API (self-hosted or public)
 * FREE - No API key needed for public servers!
 * 
 * Public servers:
 * - https://libretranslate.de (Germany, fast)
 * - https://libretranslate.com (official)
 */
function translateWithLibreTranslate($text, $sourceLang, $targetLang) {
    $apiUrl = defined('LIBRETRANSLATE_URL') ? LIBRETRANSLATE_URL : 'https://libretranslate.de/translate';
    $apiKey = defined('LIBRETRANSLATE_API_KEY') ? LIBRETRANSLATE_API_KEY : '';
    
    $data = [
        'q' => $text,
        'source' => $sourceLang,
        'target' => $targetLang,
        'format' => 'text'
    ];
    
    // Add API key if provided
    if (!empty($apiKey)) {
        $data['api_key'] = $apiKey;
    }
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 15
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['translatedText'])) {
            return $result['translatedText'];
        }
    }
    
    return $text;
}

/**
 * Batch translation using LibreTranslate (parallel requests)
 * FREE - Works with public servers
 */
function translateBatchWithLibreTranslate($textsArray, $sourceLang, $targetLang) {
    $apiUrl = defined('LIBRETRANSLATE_URL') ? LIBRETRANSLATE_URL : 'https://libretranslate.de/translate';
    $apiKey = defined('LIBRETRANSLATE_API_KEY') ? LIBRETRANSLATE_API_KEY : '';
    
    $translations = [];
    $mh = curl_multi_init();
    $handles = [];
    
    foreach ($textsArray as $key => $text) {
        $data = [
            'q' => $text,
            'source' => $sourceLang,
            'target' => $targetLang,
            'format' => 'text'
        ];
        
        if (!empty($apiKey)) {
            $data['api_key'] = $apiKey;
        }
        
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 20
        ]);
        
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }
    
    // Execute all requests in parallel
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);
    
    // Get results
    foreach ($handles as $key => $ch) {
        $response = curl_multi_getcontent($ch);
        $result = json_decode($response, true);
        
        if (isset($result['translatedText'])) {
            $translations[$key] = $result['translatedText'];
        } else {
            $translations[$key] = $textsArray[$key]; // Fallback to original
        }
        
        curl_multi_remove_handle($mh, $ch);
    }
    
    curl_multi_close($mh);
    
    return $translations;
}

/**
 * Smart translation - tries multiple providers
 * Falls back to next provider if one fails
 */
function translateSmart($text, $sourceLang, $targetLang) {
    // Try AI first (best quality)
    $translated = translateWithAI($text, $sourceLang, $targetLang);
    if ($translated !== $text) {
        return $translated;
    }
    
    // Fall back to MyMemory (free, no key)
    $translated = translateWithMyMemory($text, $sourceLang, $targetLang);
    if ($translated !== $text) {
        return $translated;
    }
    
    return $text;
}
?>

