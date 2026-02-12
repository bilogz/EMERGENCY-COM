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

// Extract JSON from response (handle accidental extra text)
$start = strpos($text, '{');
$end = strrpos($text, '}');
if ($start === false || $end === false || $end <= $start) {
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => 'AI response not in JSON format']);
    exit();
}

$json = substr($text, $start, $end - $start + 1);
$data = json_decode($json, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => 'Failed to parse AI JSON response']);
    exit();
}

$title = trim((string)($data['title'] ?? ''));
$body = trim((string)($data['body'] ?? ''));

if ($title === '' || $body === '') {
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => 'AI response missing title/body']);
    exit();
}

echo json_encode([
    'success' => true,
    'data' => [
        'title' => $title,
        'body' => $body
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
