<?php
/**
 * AI Message Suggestion (Gemini)
 * Returns JSON with { title, body } for the mass notification wizard.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Ensure fatal errors still return JSON (avoid HTTP 500 in UI)
register_shutdown_function(function () {
    $err = error_get_last();
    if (!$err) return;
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (!in_array($err['type'], $fatalTypes, true)) return;
    if (headers_sent()) return;
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $err['message'] ?? 'fatal'
    ]);
});

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

function aiStripCodeFences(string $text): string {
    $clean = trim($text);
    $clean = preg_replace('/^\s*```[a-zA-Z0-9_-]*\s*/', '', $clean);
    $clean = preg_replace('/\s*```\s*$/', '', $clean);
    return trim((string)$clean);
}

function aiCleanPlainText(string $text): string {
    $text = aiStripCodeFences($text);
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    // Remove markdown bullets and heading marks.
    $text = preg_replace('/^\s*[-*#]+\s*/m', '', $text);
    $text = preg_replace('/^\s*\d+\.\s*/m', '', $text);
    // Collapse excessive whitespace.
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim((string)$text);
}

function aiLimitTitleWords(string $title, int $maxWords = 8): string {
    $title = trim($title, " \t\n\r\0\x0B\"'");
    if ($title === '') return '';
    $words = preg_split('/\s+/', $title);
    if (!is_array($words)) return $title;
    if (count($words) > $maxWords) {
        $words = array_slice($words, 0, $maxWords);
    }
    return trim(implode(' ', $words));
}

function aiBuildSuggestion(string $title, string $body): ?array {
    $title = aiLimitTitleWords(aiCleanPlainText($title), 8);
    $body = aiCleanPlainText($body);

    if ($title === '' && $body === '') return null;
    if ($title === '') {
        // Fallback: generate short title from body.
        $title = aiLimitTitleWords($body, 8);
    }
    if ($body === '') {
        $body = $title;
    }

    if (strlen($body) > 550) {
        $body = trim(substr($body, 0, 550));
    }

    return ['title' => $title, 'body' => $body];
}

function aiDecodeNestedJsonObject(string $value): ?array {
    $candidate = aiStripCodeFences($value);
    if ($candidate === '') return null;

    $decoded = json_decode($candidate, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }

    // Sometimes the payload is a JSON-encoded string containing another JSON object.
    $decodedString = json_decode($candidate, true);
    if (json_last_error() === JSON_ERROR_NONE && is_string($decodedString)) {
        $decodedAgain = json_decode($decodedString, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedAgain)) {
            return $decodedAgain;
        }
    }

    return null;
}

function aiExtractSuggestion(string $rawText): ?array {
    $clean = aiStripCodeFences($rawText);
    if ($clean === '') return null;

    $candidates = [];
    $candidates[] = $clean;

    $start = strpos($clean, '{');
    $end = strrpos($clean, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $candidates[] = substr($clean, $start, $end - $start + 1);
    }

    foreach ($candidates as $candidate) {
        $parsed = json_decode($candidate, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue;
        }

        if (is_string($parsed)) {
            $nested = aiDecodeNestedJsonObject($parsed);
            if (is_array($nested)) {
                $parsed = $nested;
            } else {
                $fallback = aiBuildSuggestion('', $parsed);
                if ($fallback) return $fallback;
                continue;
            }
        }

        if (!is_array($parsed)) {
            continue;
        }

        $arrayCandidates = [$parsed];
        foreach (['data', 'response', 'result'] as $k) {
            if (isset($parsed[$k]) && is_array($parsed[$k])) {
                $arrayCandidates[] = $parsed[$k];
            }
        }

        foreach ($arrayCandidates as $obj) {
            $title = trim((string)($obj['title'] ?? ''));
            $body = trim((string)($obj['body'] ?? ''));

            if (($title === '' || $body === '') && isset($obj['message'])) {
                $body = trim((string)$obj['message']);
            }

            // Handle escaped JSON dumped into title/body text.
            if ($title !== '' && ($body === '' || strpos($title, '{') !== false)) {
                $nested = aiDecodeNestedJsonObject($title);
                if (is_array($nested)) {
                    $title = trim((string)($nested['title'] ?? $title));
                    $body = trim((string)($nested['body'] ?? $body));
                }
            }
            if ($body !== '' && strpos($body, '{') !== false) {
                $nested = aiDecodeNestedJsonObject($body);
                if (is_array($nested)) {
                    $title = trim((string)($nested['title'] ?? $title));
                    $body = trim((string)($nested['body'] ?? $body));
                }
            }

            $suggestion = aiBuildSuggestion($title, $body);
            if ($suggestion) return $suggestion;
        }
    }

    // Fallback parser for non-JSON responses such as:
    // Title: ...
    // Body: ...
    $title = '';
    $body = '';
    if (preg_match('/^\s*(?:title|headline)\s*[:\-]\s*(.+)$/im', $clean, $m)) {
        $title = trim((string)$m[1]);
    }
    if (preg_match('/^\s*(?:body|message|content)\s*[:\-]\s*([\s\S]+)$/im', $clean, $m)) {
        $body = trim((string)$m[1]);
    }

    if ($title !== '' || $body !== '') {
        $suggestion = aiBuildSuggestion($title, $body);
        if ($suggestion) return $suggestion;
    }

    // Final fallback: derive title from first line/sentence and keep rest as body.
    $normalized = aiCleanPlainText($clean);
    if ($normalized === '') return null;

    $parts = preg_split('/\n+/', $normalized);
    if (!is_array($parts) || count($parts) === 0) return null;

    $first = trim((string)$parts[0]);
    $rest = trim(implode("\n", array_slice($parts, 1)));
    if ($rest === '' && preg_match('/^(.{1,90}[.!?])\s+(.+)$/s', $normalized, $m)) {
        $first = trim((string)$m[1]);
        $rest = trim((string)$m[2]);
    }

    return aiBuildSuggestion($first, $rest !== '' ? $rest : $normalized);
}

try {
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
require_once __DIR__ . '/gemini-api-wrapper.php';
// Load DB connection so key lookup can also use API key management tables.
if (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php';
}

$apiKey = getGeminiApiKey('ai_message');
if (empty($apiKey)) {
    $apiKey = getGeminiApiKey('default');
}
if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'AI API key not configured. Please set AI_API_KEY_AI_MESSAGE (or AI_API_KEY) in config.local.php, or enable the key in API Key Management.',
        'error_code' => 'missing_api_key'
    ]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
    exit();
}

$catName = trim((string)($input['catName'] ?? 'General Alert'));
$catDesc = trim((string)($input['catDesc'] ?? ''));
$severity = trim((string)($input['severity'] ?? 'medium'));
$audienceType = trim((string)($input['audienceType'] ?? 'all'));
$barangay = trim((string)($input['barangay'] ?? ''));
$role = trim((string)($input['role'] ?? ''));
$weatherSignal = trim((string)($input['weatherSignal'] ?? ''));
$fireLevel = trim((string)($input['fireLevel'] ?? ''));

$where = 'Quezon City';
if ($audienceType === 'barangay' && $barangay !== '') {
    $where = $barangay . ', Quezon City';
} elseif ($audienceType === 'role' && $role !== '') {
    $where = $role . ' users in Quezon City';
}

$contextLines = [];
$contextLines[] = "Category: {$catName}";
if ($catDesc !== '') $contextLines[] = "Category description: {$catDesc}";
$contextLines[] = "Severity: {$severity}";
$contextLines[] = "Audience: {$audienceType}";
$contextLines[] = "Location: {$where}";
if ($weatherSignal !== '') $contextLines[] = "Weather signal: {$weatherSignal}";
if ($fireLevel !== '') $contextLines[] = "Fire alert level: {$fireLevel}";

$categoryRules = "Category rules:\n"
    . "- Only reference hazards/actions relevant to the category name.\n"
    . "- Do not mention unrelated hazards or other categories.\n"
    . "- If category is weather/typhoon/rain/flood: include the signal number if provided.\n"
    . "- If category is fire/smoke: include the fire alert level if provided.\n";

$prompt = "You are an emergency alert assistant for Quezon City.\n"
    . "Generate a SHORT, action-focused alert draft.\n"
    . "Return ONLY valid JSON with keys: title, body.\n"
    . "Constraints:\n"
    . "- Title max 8 words.\n"
    . "- Body 2-5 short sentences, <= 550 characters.\n"
    . "- Use clear, simple language. Include what, where, and what to do next.\n"
    . "- No emojis, no markdown, no code fences.\n"
    . "- Tone rules: if severity is low, make it calm and reminder-like. Avoid urgency words like 'act now', 'evacuate', 'emergency'.\n"
    . "- If severity is medium/high/critical, keep appropriate urgency but stay calm and professional.\n"
    . $categoryRules
    . "\nContext:\n"
    . implode("\n", $contextLines);

$model = getGeminiModel();
$result = callGeminiWithAutoRotation($prompt, 'ai_message', $model, [
    'temperature' => 0.5,
    'maxOutputTokens' => 600
]);

if (empty($result['success'])) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'message' => 'AI request failed',
        'error' => $result['error'] ?? 'Unknown error',
        'error_code' => 'ai_request_failed',
        'model' => $model
    ]);
    exit();
}

$text = trim((string)($result['data'] ?? ''));
if ($text === '') {
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => 'Empty AI response']);
    exit();
}

$suggestion = aiExtractSuggestion($text);
if (!$suggestion) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'message' => 'AI response could not be converted to title/body',
        'error_code' => 'ai_response_unusable'
    ]);
    exit();
}

echo json_encode([
    'success' => true,
    'data' => [
        'title' => $suggestion['title'],
        'body' => $suggestion['body']
    ],
    'model' => $model
]);
} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'message' => 'AI request failed',
        'error' => $e->getMessage(),
        'error_code' => 'exception'
    ]);
}
