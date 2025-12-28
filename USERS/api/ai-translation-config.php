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
$geminiModel = $secureConfig['GEMINI_MODEL'] ?? $_ENV['GEMINI_MODEL'] ?? 'gemini-2.0-flash-exp';

// Define constants
define('AI_PROVIDER', $aiProvider);
define('AI_API_KEY', $aiApiKey);
define('GEMINI_MODEL', $geminiModel);

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
?>

