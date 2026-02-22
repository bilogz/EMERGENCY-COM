<?php
/**
 * Chatbot Assistant Endpoint (User)
 * Securely calls Gemini from the backend.
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/config.env.php';
require_once __DIR__ . '/../../ADMIN/api/secure-api-config.php';
require_once __DIR__ . '/../../ADMIN/api/gemini-api-wrapper.php';

/**
 * @param mixed $value
 */
function chatbot_to_bool($value): bool {
    if (is_bool($value)) {
        return $value;
    }
    $raw = strtolower(trim((string)$value));
    if ($raw === '') {
        return true;
    }
    return !in_array($raw, ['0', 'false', 'off', 'no'], true);
}

function chatbot_clean_text(string $text): string {
    $text = trim($text);
    $text = preg_replace('/^\s*```[a-zA-Z0-9_-]*\s*/', '', $text);
    $text = preg_replace('/\s*```\s*$/', '', $text);
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim((string)$text);
}

function chatbot_normalize_for_match(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/\s+/', ' ', $text);
    return trim((string)$text);
}

/**
 * @param string[] $patterns
 */
function chatbot_matches_any(string $text, array $patterns): bool {
    if ($text === '') {
        return false;
    }
    foreach ($patterns as $pattern) {
        if (!is_string($pattern) || $pattern === '') {
            continue;
        }
        if (@preg_match($pattern, $text) === 1) {
            return true;
        }
    }
    return false;
}

function chatbot_detect_language_from_text(string $text): string {
    $normalized = chatbot_normalize_for_match($text);
    if ($normalized === '') {
        return 'unknown';
    }

    // Explicit user preference
    if (chatbot_matches_any($normalized, [
        '/\b(reply in english|speak english|english please|in english)\b/i',
    ])) {
        return 'en';
    }
    if (chatbot_matches_any($normalized, [
        '/\b(reply in tagalog|speak tagalog|tagalog please|in tagalog|filipino please)\b/i',
        '/\b(tagalog|filipino)\b/i',
    ])) {
        return 'fil';
    }

    $tagalogPatterns = [
        '/\b(ako|ikaw|kayo|siya|kami|tayo|nila|namin|natin)\b/i',
        '/\b(ano|saan|kailan|bakit|paano|sino)\b/i',
        '/\b(po|opo|paki|pakisuyo|pwedeng|pwede)\b/i',
        '/\b(kailangan|tulong|emergency|kagyat|delikado)\b/i',
        '/\b(may|wala|hindi|nasa|dito|doon|barangay)\b/i',
        '/\b(sunog|baha|lindol|aksidente|nasaktan|nasusunog|nahimatay)\b/i',
    ];

    $englishPatterns = [
        '/\b(the|is|are|was|were|please|help|urgent)\b/i',
        '/\b(what|where|when|why|how|who)\b/i',
        '/\b(emergency|incident|location|injured|fire|flood|earthquake)\b/i',
        '/\b(call|send|share|need|report|assistance)\b/i',
    ];

    $tagalogScore = 0;
    foreach ($tagalogPatterns as $pattern) {
        if (preg_match($pattern, $normalized) === 1) {
            $tagalogScore++;
        }
    }

    $englishScore = 0;
    foreach ($englishPatterns as $pattern) {
        if (preg_match($pattern, $normalized) === 1) {
            $englishScore++;
        }
    }

    if ($tagalogScore >= 2 && $tagalogScore >= $englishScore) {
        return 'fil';
    }
    if ($englishScore >= 2 && $englishScore > $tagalogScore) {
        return 'en';
    }
    if ($tagalogScore === 1 && $englishScore === 0) {
        return 'fil';
    }
    if ($englishScore === 1 && $tagalogScore === 0) {
        return 'en';
    }

    return 'unknown';
}

/**
 * @param string[] $historyUserMessages
 */
function chatbot_detect_preferred_language(string $message, array $historyUserMessages, string $locale): string {
    $messageLang = chatbot_detect_language_from_text($message);
    if ($messageLang !== 'unknown') {
        return $messageLang;
    }

    for ($i = count($historyUserMessages) - 1; $i >= 0; $i--) {
        $candidate = trim((string)$historyUserMessages[$i]);
        if ($candidate === '') {
            continue;
        }
        $lang = chatbot_detect_language_from_text($candidate);
        if ($lang !== 'unknown') {
            return $lang;
        }
    }

    $localeRaw = strtolower(trim($locale));
    if (preg_match('/^(tl|fil)(-|$)/', $localeRaw) === 1) {
        return 'fil';
    }

    return 'en';
}

function chatbot_language_name(string $languageCode): string {
    return $languageCode === 'fil' ? 'Filipino (Tagalog)' : 'English';
}

function chatbot_detect_incident_type(string $text): string {
    $normalized = chatbot_normalize_for_match($text);
    if ($normalized === '') {
        return 'general_support';
    }

    $incidentPatterns = [
        'medical_emergency' => [
            '/\b(heart attack|stroke|seizure|unconscious|not breathing|difficulty breathing|overdose|bleeding|collapsed|ambulance)\b/i',
            '/\b(nahimatay|hindi humihinga|hirap huminga|atake sa puso|dumudugo|na-collapse)\b/i',
        ],
        'fire' => [
            '/\b(fire|burning|smoke|flame|explosion|gas leak)\b/i',
            '/\b(sunog|umuusok|sumabog|tagas ng gas)\b/i',
        ],
        'crime_violence' => [
            '/\b(robbery|hold[- ]?up|stabbing|shooting|assault|kidnapping|violence|gun)\b/i',
            '/\b(nanakaw|hinoldap|sinaksak|binaril|karahasan|baril)\b/i',
        ],
        'road_accident' => [
            '/\b(accident|collision|crash|hit and run|vehicle|motorcycle|car crash)\b/i',
            '/\b(aksidente|banggaan|nabangga|hit and run|motor|sasakyan)\b/i',
        ],
        'flood' => [
            '/\b(flood|flooding|flash flood|rising water|inundation)\b/i',
            '/\b(baha|bumabaha|taas ng tubig|lubog)\b/i',
        ],
        'earthquake' => [
            '/\b(earthquake|aftershock|ground shaking|tectonic)\b/i',
            '/\b(lindol|aftershock|pagyanig)\b/i',
        ],
        'landslide' => [
            '/\b(landslide|soil collapse|rockslide|slope failure)\b/i',
            '/\b(pagguho|landslide|gumuho)\b/i',
        ],
        'typhoon_storm' => [
            '/\b(typhoon|storm|thunderstorm|strong winds|tornado)\b/i',
            '/\b(bagyo|unos|malakas na hangin|kulog|kidlat)\b/i',
        ],
        'electrical_hazard' => [
            '/\b(electrical fire|electrocution|live wire|short circuit|power line)\b/i',
            '/\b(kuryente|nakuryente|live wire|short circuit)\b/i',
        ],
        'missing_person' => [
            '/\b(missing person|lost child|missing child|cannot find)\b/i',
            '/\b(nawawala|hindi mahanap|missing)\b/i',
        ],
        'rescue_request' => [
            '/\b(trapped|rescue|stranded|need evacuation|cannot get out)\b/i',
            '/\b(nakakulong|na-trap|rescue|evacuate|nastranded)\b/i',
        ],
    ];

    foreach ($incidentPatterns as $incidentType => $patterns) {
        if (chatbot_matches_any($normalized, $patterns)) {
            return $incidentType;
        }
    }

    if (chatbot_matches_any($normalized, [
        '/\b(emergency|urgent|critical|sos|help now)\b/i',
        '/\b(emergency|kagyat|kailangan ng tulong)\b/i',
    ])) {
        return 'general_emergency';
    }

    return 'general_support';
}

function chatbot_incident_label(string $incidentType, string $languageCode = 'en'): string {
    $labelsEn = [
        'medical_emergency' => 'Medical Emergency',
        'fire' => 'Fire Incident',
        'crime_violence' => 'Crime/Violence Incident',
        'road_accident' => 'Road Accident',
        'flood' => 'Flood Incident',
        'earthquake' => 'Earthquake Incident',
        'landslide' => 'Landslide Incident',
        'typhoon_storm' => 'Typhoon/Storm Incident',
        'electrical_hazard' => 'Electrical Hazard',
        'missing_person' => 'Missing Person',
        'rescue_request' => 'Rescue Request',
        'general_emergency' => 'General Emergency',
        'general_support' => 'General Concern',
    ];
    $labelsFil = [
        'medical_emergency' => 'Emerhensiyang Medikal',
        'fire' => 'Insidente ng Sunog',
        'crime_violence' => 'Insidente ng Krimen/Karahasan',
        'road_accident' => 'Aksidente sa Kalsada',
        'flood' => 'Insidente ng Baha',
        'earthquake' => 'Insidente ng Lindol',
        'landslide' => 'Insidente ng Pagguho ng Lupa',
        'typhoon_storm' => 'Insidente ng Bagyo/Unos',
        'electrical_hazard' => 'Panganib sa Kuryente',
        'missing_person' => 'Nawawalang Tao',
        'rescue_request' => 'Kahilingan sa Pagsagip',
        'general_emergency' => 'Pangkalahatang Emerhensiya',
        'general_support' => 'Pangkalahatang Concern',
    ];

    $labels = $languageCode === 'fil' ? $labelsFil : $labelsEn;
    return $labels[$incidentType] ?? ($languageCode === 'fil' ? 'Pangkalahatang Concern' : 'General Concern');
}

function chatbot_incident_follow_up(string $incidentType, string $languageCode = 'en'): string {
    $followUpsEn = [
        'medical_emergency' => 'Tell me patient condition, age estimate, and exact location/barangay.',
        'fire' => 'Share fire size, exact location/barangay, and if people are trapped.',
        'crime_violence' => 'Share suspect details, current danger level, and exact location/barangay.',
        'road_accident' => 'Share number of vehicles involved, injured count, and exact location/barangay.',
        'flood' => 'Share water depth, evacuation need, and exact location/barangay.',
        'earthquake' => 'Share structural damage, injuries, and exact location/barangay.',
        'landslide' => 'Share affected area, trapped persons, and exact location/barangay.',
        'typhoon_storm' => 'Share current hazards (winds/fallen lines/flood), and exact location/barangay.',
        'electrical_hazard' => 'Share hazard source, if power is still active, and exact location/barangay.',
        'missing_person' => 'Share last seen location/time, clothing details, and contact number.',
        'rescue_request' => 'Share how many are trapped, immediate hazards, and exact location/barangay.',
        'general_emergency' => 'Share exact location/barangay, what is happening now, and number of affected people.',
        'general_support' => 'Share exact location/barangay, incident type, and what help you need.',
    ];
    $followUpsFil = [
        'medical_emergency' => 'Ibigay ang kalagayan ng pasyente, tinatayang edad, at eksaktong lokasyon/barangay.',
        'fire' => 'Ibigay ang laki ng sunog, eksaktong lokasyon/barangay, at kung may taong na-trap.',
        'crime_violence' => 'Ibigay ang detalye ng suspek, antas ng panganib ngayon, at eksaktong lokasyon/barangay.',
        'road_accident' => 'Ibigay ang bilang ng sangkot na sasakyan, bilang ng sugatan, at eksaktong lokasyon/barangay.',
        'flood' => 'Ibigay ang taas ng baha, kung kailangan ng evacuation, at eksaktong lokasyon/barangay.',
        'earthquake' => 'Ibigay ang pinsala sa istruktura, bilang ng sugatan, at eksaktong lokasyon/barangay.',
        'landslide' => 'Ibigay ang apektadong lugar, kung may na-trap, at eksaktong lokasyon/barangay.',
        'typhoon_storm' => 'Ibigay ang kasalukuyang panganib (hangin/bagsak na linya/baha) at eksaktong lokasyon/barangay.',
        'electrical_hazard' => 'Ibigay ang pinanggagalingan ng panganib, kung may live power pa, at eksaktong lokasyon/barangay.',
        'missing_person' => 'Ibigay ang huling lokasyon/oras na nakita, suot na damit, at contact number.',
        'rescue_request' => 'Ibigay kung ilan ang na-trap, anong agarang panganib, at eksaktong lokasyon/barangay.',
        'general_emergency' => 'Ibigay ang eksaktong lokasyon/barangay, ano ang nangyayari ngayon, at ilan ang apektado.',
        'general_support' => 'Ibigay ang eksaktong lokasyon/barangay, uri ng insidente, at anong tulong ang kailangan.',
    ];

    $followUps = $languageCode === 'fil' ? $followUpsFil : $followUpsEn;
    return $followUps[$incidentType] ?? ($languageCode === 'fil' ? $followUpsFil['general_support'] : $followUpsEn['general_support']);
}

function chatbot_detect_emergency(string $text, string $incidentType): bool {
    $normalized = chatbot_normalize_for_match($text);
    if ($normalized === '') {
        return false;
    }

    if (chatbot_matches_any($normalized, [
        '/\b(no emergency|not emergency|for school|for project|research only|drill|simulation)\b/i',
        '/\b(hindi emergency|hindi to emergency)\b/i',
        '/\b(minor|no injuries|no injury|no one hurt|just reporting|for report only|already resolved|resolved)\b/i',
        '/\b(walang nasaktan|minor lang|nakaresolba na|resolved na)\b/i',
    ])) {
        return false;
    }

    if (chatbot_matches_any($normalized, [
        '/\b(urgent|emergency|critical|immediate|sos|help now|life[- ]?threatening|dying|severe)\b/i',
        '/\b(kagyat|critical|agarang tulong|emergency|delikado)\b/i',
    ])) {
        return true;
    }

    if (in_array($incidentType, ['medical_emergency', 'fire', 'crime_violence', 'rescue_request'], true)) {
        return true;
    }

    if ($incidentType === 'road_accident' && chatbot_matches_any($normalized, [
        '/\b(injured|trapped|bleeding|unconscious|severe|critical|pile[- ]?up|multiple vehicles)\b/i',
        '/\b(may sugatan|may na[- ]?trap|dugo|walang malay|malala)\b/i',
    ])) {
        return true;
    }

    if (in_array($incidentType, ['flood', 'earthquake', 'landslide', 'typhoon_storm', 'electrical_hazard'], true)
        && chatbot_matches_any($normalized, [
            '/\b(now|right now|ongoing|currently|happening)\b/i',
            '/\b(ngayon|kasalukuyan|nangyayari)\b/i',
        ])
    ) {
        return true;
    }

    return $incidentType === 'general_emergency';
}

function chatbot_normalize_url(string $rawUrl): string {
    $rawUrl = trim($rawUrl);
    if ($rawUrl === '') {
        return '';
    }

    if (strpos($rawUrl, '//') === 0) {
        $rawUrl = 'https:' . $rawUrl;
    } elseif (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $rawUrl)) {
        if ($rawUrl[0] === '/') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = trim((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $rawUrl = $scheme . '://' . $host . $rawUrl;
        } else {
            $rawUrl = 'https://' . ltrim($rawUrl, '/');
        }
    }

    $parts = parse_url($rawUrl);
    if (!is_array($parts) || empty($parts['host'])) {
        return '';
    }
    $scheme = strtolower((string)($parts['scheme'] ?? 'https'));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return '';
    }

    return $rawUrl;
}

function chatbot_get_emergency_call_link(): string {
    $configuredUrl = trim((string)getSecureConfig('CHAT_ASSISTANT_EMERGENCY_CALL_URL', ''));
    if ($configuredUrl !== '') {
        $normalized = chatbot_normalize_url($configuredUrl);
        if ($normalized !== '') {
            return $normalized;
        }
    }

    $fallbackConfigUrl = trim((string)getSecureConfig('EMERGENCY_CALL_PUBLIC_URL', ''));
    if ($fallbackConfigUrl !== '') {
        $normalizedFallback = chatbot_normalize_url($fallbackConfigUrl);
        if ($normalizedFallback !== '') {
            return $normalizedFallback;
        }
    }

    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $projectBase = '/EMERGENCY-COM';
    if (preg_match('#^(.*/EMERGENCY-COM)(?:/|$)#i', $scriptName, $m)) {
        $projectBase = $m[1];
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = trim((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return $scheme . '://' . $host . $projectBase . '/USERS/emergency-call.php';
}

function chatbot_get_emergency_number(): string {
    $configuredNumber = trim((string)getSecureConfig('CHAT_ASSISTANT_EMERGENCY_NUMBER', ''));
    if ($configuredNumber === '') {
        $configuredNumber = trim((string)getSecureConfig('EMERGENCY_HOTLINE_NUMBER', '122'));
    }

    $normalized = preg_replace('/[^0-9+]/', '', $configuredNumber);
    if (!is_string($normalized) || trim($normalized) === '') {
        return '122';
    }

    return trim($normalized);
}

function chatbot_build_local_reply(
    string $message,
    string $incidentType,
    bool $isEmergency,
    string $callLink,
    string $emergencyNumber,
    string $languageCode = 'en'
): string {
    $incidentLabel = chatbot_incident_label($incidentType, $languageCode);
    $followUp = chatbot_incident_follow_up($incidentType, $languageCode);
    $normalized = chatbot_normalize_for_match($message);
    $wantsClassification = chatbot_matches_any($normalized, [
        '/\b(what incident|what type|classif|anong incident|anong concern|ano ito)\b/i',
    ]);

    if ($isEmergency) {
        $lines = $languageCode === 'fil'
            ? [
                'Posibleng ' . $incidentLabel . ' ang natukoy.',
                'Tumawag agad sa ' . $emergencyNumber . ' kung may banta sa buhay.',
            ]
            : [
                'Possible ' . $incidentLabel . ' detected.',
                'Call ' . $emergencyNumber . ' immediately if life is at risk.',
            ];
        if ($callLink !== '') {
            $lines[] = $languageCode === 'fil'
                ? 'Link para sa emergency call: ' . $callLink
                : 'Emergency call link: ' . $callLink;
        }
        $lines[] = $languageCode === 'fil'
            ? 'Ibigay ang eksaktong lokasyon/barangay at pinakamalapit na landmark.'
            : 'Send exact location/barangay and nearest landmark.';
        $lines[] = $followUp;
        return chatbot_clean_text(implode("\n", $lines));
    }

    if ($wantsClassification && $incidentType !== 'general_support') {
        $lines = $languageCode === 'fil'
            ? [
                'Mukhang ito ay: ' . $incidentLabel . '.',
                $followUp,
                'Kapag lumala ang panganib, tumawag agad sa ' . $emergencyNumber . '.'
            ]
            : [
                'This appears to be: ' . $incidentLabel . '.',
                $followUp,
                'If danger increases, call ' . $emergencyNumber . ' immediately.'
            ];
        if ($callLink !== '') {
            $lines[] = $languageCode === 'fil'
                ? 'Link para sa emergency call: ' . $callLink
                : 'Emergency call link: ' . $callLink;
        }
        return chatbot_clean_text(implode("\n", $lines));
    }

    $lines = $languageCode === 'fil'
        ? [
            'Makakatulong ako sa Quezon City incident triage.',
            'Kasalukuyang kategorya ng insidente: ' . $incidentLabel . '.',
            $followUp,
        ]
        : [
            'I can help with Quezon City incident triage.',
            'Current incident category: ' . $incidentLabel . '.',
            $followUp,
        ];
    if ($callLink !== '') {
        $lines[] = $languageCode === 'fil'
            ? 'Para sa agarang kaso, gamitin ito: ' . $callLink
            : 'For urgent cases, use: ' . $callLink;
    }
    return chatbot_clean_text(implode("\n", $lines));
}

function chatbot_enforce_emergency_reply(
    string $reply,
    string $incidentType,
    string $callLink,
    string $emergencyNumber,
    string $languageCode = 'en'
): string {
    $reply = chatbot_clean_text($reply);
    $incidentLabel = chatbot_incident_label($incidentType, $languageCode);
    $lines = [];

    if (!chatbot_matches_any(strtolower($reply), [
        '/\b(emergency|urgent|critical|emerhensiya|kagyat|delikado)\b/i',
    ])) {
        $lines[] = $languageCode === 'fil'
            ? 'Posibleng ' . $incidentLabel . ' na emerhensiya ang natukoy.'
            : 'Possible ' . $incidentLabel . ' emergency detected.';
    }

    if ($reply !== '') {
        $lines[] = $reply;
    }

    $combined = trim(implode("\n", $lines));

    $numberPattern = '/(?<!\\d)' . preg_quote($emergencyNumber, '/') . '(?!\\d)/';
    if (@preg_match($numberPattern, $combined) !== 1) {
        $combined .= ($combined === '' ? '' : "\n") . (
            $languageCode === 'fil'
                ? 'Tumawag agad sa ' . $emergencyNumber . ' kung may banta sa buhay.'
                : 'Call ' . $emergencyNumber . ' immediately if life is at risk.'
        );
    }

    if ($callLink !== '' && stripos($combined, $callLink) === false) {
        $combined .= ($combined === '' ? '' : "\n") . (
            $languageCode === 'fil'
                ? 'Link para sa emergency call: '
                : 'Emergency call link: '
        ) . $callLink;
    }

    return chatbot_clean_text($combined);
}

try {
    $raw = file_get_contents('php://input');
    $input = json_decode((string)$raw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $message = trim((string)($input['message'] ?? ''));
    if ($message === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit;
    }

    $assistantEnabled = chatbot_to_bool(getSecureConfig('CHAT_ASSISTANT_ENABLED', true));
    if (!$assistantEnabled) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'message' => 'AI assistant is disabled by configuration.'
        ]);
        exit;
    }

    $apiKey = trim((string)getSecureConfig('AI_API_KEY_CHATBOT', ''));
    if ($apiKey === '') {
        $apiKey = trim((string)getSecureConfig('AI_API_KEY_AI_MESSAGE', ''));
    }
    if ($apiKey === '' && function_exists('getGeminiApiKey')) {
        $apiKey = trim((string)(getGeminiApiKey('ai_message') ?? ''));
        if ($apiKey === '') {
            $apiKey = trim((string)(getGeminiApiKey('default') ?? ''));
        }
    }

    if ($apiKey === '') {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'AI API key is not configured. Set AI_API_KEY_CHATBOT (or AI_API_KEY_AI_MESSAGE) in config.local.php.'
        ]);
        exit;
    }

    $configuredModel = trim((string)getSecureConfig('CHAT_ASSISTANT_MODEL', ''));
    if ($configuredModel === '') {
        $configuredModel = trim((string)getSecureConfig('GEMINI_MODEL', ''));
    }
    if ($configuredModel === '' && function_exists('getGeminiModel')) {
        $configuredModel = trim((string)getGeminiModel());
    }
    if ($configuredModel === '') {
        $configuredModel = 'gemini-2.5-flash';
    }

    $defaultSystemPrompt = "You are the Quezon City Emergency Communication AI assistant.\n"
        . "Rules:\n"
        . "- Keep replies practical, short, and clear.\n"
        . "- Match the user's language. If user writes in Filipino/Tagalog, reply in Filipino/Tagalog.\n"
        . "- If the user reports danger, treat it as urgent and prioritize immediate safety.\n"
        . "- If user asks what incident type it is, classify into one category and explain briefly.\n"
        . "- Always ask for exact location/barangay when needed.\n"
        . "- For life-threatening emergencies, always advise calling the official emergency hotline immediately.\n"
        . "- Do not invent hotlines, addresses, or official contacts.\n"
        . "- If unsure, clearly say what you are unsure about.\n"
        . "- Use plain text only (no markdown).";
    $systemPrompt = trim((string)getSecureConfig('CHAT_ASSISTANT_SYSTEM_PROMPT', $defaultSystemPrompt));
    if ($systemPrompt === '') {
        $systemPrompt = $defaultSystemPrompt;
    }

    $locale = trim((string)($input['locale'] ?? 'en-US'));
    if ($locale === '') {
        $locale = 'en-US';
    }

    $incidentType = chatbot_detect_incident_type($message);
    $isEmergency = chatbot_detect_emergency($message, $incidentType);
    $callLink = chatbot_get_emergency_call_link();
    $emergencyNumber = chatbot_get_emergency_number();

    $history = $input['history'] ?? [];
    $historyLines = [];
    $historyUserMessages = [];
    if (is_array($history)) {
        $history = array_slice($history, -12);
        foreach ($history as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $roleRaw = strtolower(trim((string)($entry['role'] ?? 'user')));
            $role = $roleRaw === 'assistant' ? 'Assistant' : 'User';
            $content = trim((string)($entry['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $content = preg_replace('/\s+/', ' ', $content);
            $content = substr($content, 0, 500);
            $historyLines[] = $role . ': ' . $content;
            if ($roleRaw === 'user') {
                $historyUserMessages[] = $content;
            }
        }
    }

    $preferredLanguage = chatbot_detect_preferred_language($message, $historyUserMessages, $locale);
    $preferredLanguageName = chatbot_language_name($preferredLanguage);
    $incidentLabel = chatbot_incident_label($incidentType, $preferredLanguage);
    $followUpHint = chatbot_incident_follow_up($incidentType, $preferredLanguage);

    $prompt = $systemPrompt . "\n\n"
        . "Server routing context:\n"
        . "- Service location: Quezon City, Philippines.\n"
        . "- emergency_detected: " . ($isEmergency ? 'true' : 'false') . "\n"
        . "- incident_type: " . $incidentType . "\n"
        . "- incident_label: " . $incidentLabel . "\n"
        . "- response_language_code: " . $preferredLanguage . "\n"
        . "- response_language_name: " . $preferredLanguageName . "\n"
        . "- emergency_number: " . $emergencyNumber . "\n"
        . "- emergency_call_link: " . ($callLink !== '' ? $callLink : 'not_configured') . "\n"
        . "- Always reply in response_language_name.\n"
        . "- If response_language_code=fil, use natural Filipino (Tagalog). Avoid switching to English except URLs, numbers, and proper names.\n"
        . "- If emergency_detected=true and response_language_code=en: include 'Call " . $emergencyNumber . " immediately if life is at risk.'\n"
        . "- If emergency_detected=true and response_language_code=fil: include 'Tumawag agad sa " . $emergencyNumber . " kung may banta sa buhay.'\n"
        . "- If emergency_detected=true: include the emergency_call_link exactly once.\n"
        . "- If user asks for classification (e.g., 'what incident is this'), answer with one category from:\n"
        . "  fire, medical_emergency, crime_violence, road_accident, flood, earthquake, landslide, typhoon_storm, electrical_hazard, missing_person, rescue_request, general_support.\n"
        . "- Follow-up hint for this case: " . $followUpHint . "\n"
        . "- Keep response under 6 short sentences.\n\n"
        . "Preferred locale: " . $locale . "\n"
        . "Conversation:\n";

    if (!empty($historyLines)) {
        $prompt .= implode("\n", $historyLines) . "\n";
    }

    $prompt .= "User: " . preg_replace('/\s+/', ' ', $message) . "\n";
    $prompt .= "Assistant:";

    $usedRuleFallback = false;

    $result = callGeminiApi($apiKey, $prompt, $configuredModel, [
        'temperature' => 0.4,
        'maxOutputTokens' => 420,
        'timeout' => 35
    ]);

    if (empty($result['success'])) {
        // Fallback to managed key rotation if available.
        $fallback = callGeminiWithAutoRotation($prompt, 'ai_message', $configuredModel, [
            'temperature' => 0.4,
            'maxOutputTokens' => 420,
            'timeout' => 35
        ]);
        if (!empty($fallback['success'])) {
            $result = $fallback;
        }
    }

    $reply = '';
    if (!empty($result['success'])) {
        $reply = chatbot_clean_text((string)($result['data'] ?? ''));
    }

    if ($reply !== '' && $preferredLanguage === 'fil') {
        $replyLanguage = chatbot_detect_language_from_text($reply);
        if ($replyLanguage !== 'fil') {
            $translationPrompt = "Translate the following response into natural Filipino (Tagalog).\n"
                . "Rules:\n"
                . "- Keep the same meaning and urgency.\n"
                . "- Keep URLs, hotline numbers, and proper nouns unchanged.\n"
                . "- Plain text only.\n\n"
                . "Text:\n" . $reply . "\n\nTranslated:";

            $translationResult = callGeminiApi($apiKey, $translationPrompt, $configuredModel, [
                'temperature' => 0.2,
                'maxOutputTokens' => 420,
                'timeout' => 20
            ]);

            if (empty($translationResult['success'])) {
                $translationFallback = callGeminiWithAutoRotation($translationPrompt, 'ai_message', $configuredModel, [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 420,
                    'timeout' => 20
                ]);
                if (!empty($translationFallback['success'])) {
                    $translationResult = $translationFallback;
                }
            }

            if (!empty($translationResult['success'])) {
                $translatedReply = chatbot_clean_text((string)($translationResult['data'] ?? ''));
                if ($translatedReply !== '') {
                    $reply = $translatedReply;
                }
            }
        }
    }

    if ($reply === '') {
        $usedRuleFallback = true;
        $reply = chatbot_build_local_reply(
            $message,
            $incidentType,
            $isEmergency,
            $callLink,
            $emergencyNumber,
            $preferredLanguage
        );
    }

    if ($isEmergency) {
        $reply = chatbot_enforce_emergency_reply(
            $reply,
            $incidentType,
            $callLink,
            $emergencyNumber,
            $preferredLanguage
        );
    }

    if (strlen($reply) > 1800) {
        $reply = trim(substr($reply, 0, 1800));
    }

    $replyLanguageDetected = chatbot_detect_language_from_text($reply);

    echo json_encode([
        'success' => true,
        'reply' => $reply,
        'model' => $usedRuleFallback ? 'rule-fallback' : $configuredModel,
        'timestamp' => round(microtime(true) * 1000),
        'emergencyDetected' => $isEmergency,
        'incidentType' => $incidentType,
        'incidentLabel' => $incidentLabel,
        'emergencyNumber' => $emergencyNumber,
        'callLink' => $callLink,
        'usedRuleFallback' => $usedRuleFallback,
        'preferredLanguage' => $preferredLanguage,
        'replyLanguage' => $replyLanguageDetected
    ]);
} catch (Throwable $e) {
    error_log('chatbot-assistant error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unexpected server error.',
        'error' => $e->getMessage()
    ]);
}
