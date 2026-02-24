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
require_once __DIR__ . '/device_tracking.php';
require_once __DIR__ . '/../../ADMIN/api/secure-api-config.php';
require_once __DIR__ . '/../../ADMIN/api/gemini-api-wrapper.php';
require_once __DIR__ . '/../../ADMIN/api/chat-logic.php';

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

function chatbot_limit_reply_length(string $reply, int $maxChars = 1800): string {
    $reply = trim($reply);
    if ($reply === '' || strlen($reply) <= $maxChars) {
        return $reply;
    }

    $truncated = trim(substr($reply, 0, $maxChars));
    $lastSentence = max(
        strrpos($truncated, '.'),
        strrpos($truncated, '!'),
        strrpos($truncated, '?'),
        strrpos($truncated, "\n")
    );

    if ($lastSentence !== false && $lastSentence > (int)($maxChars * 0.55)) {
        $truncated = trim(substr($truncated, 0, $lastSentence + 1));
    } else {
        $truncated = rtrim($truncated, " \t\n\r\0\x0B,;:-");
        $truncated .= '...';
    }

    return trim($truncated);
}

function chatbot_finalize_reply(string $reply, string $languageCode, string $followUpHint): string {
    $reply = chatbot_clean_text($reply);
    if ($reply === '') {
        return $reply;
    }

    $reply = preg_replace('/\s+([,.!?])/u', '$1', $reply);
    $reply = trim((string)$reply);

    $hasTerminalPunctuation = preg_match('/[.!?]["\')\]]*\s*$/u', $reply) === 1;
    if ($hasTerminalPunctuation) {
        return chatbot_clean_text($reply);
    }

    // Remove obvious dangling one/two-letter tail tokens from cut-off model output.
    if (preg_match('/\s+[^\s]{1,2}$/u', $reply) === 1) {
        $reply = preg_replace('/\s+[^\s]{1,2}$/u', '', $reply);
        $reply = trim((string)$reply);
    }

    $fallbackEnding = $languageCode === 'fil'
        ? 'Pakibigay ang eksaktong lokasyon/barangay at mahahalagang detalye para maipasa ko nang tama.'
        : 'Please share exact location/barangay and key details so I can route this correctly.';
    $ending = trim($followUpHint) !== '' ? trim($followUpHint) : $fallbackEnding;

    $reply = rtrim($reply, " \t\n\r\0\x0B,;:-");
    if ($reply === '') {
        return chatbot_clean_text($ending);
    }

    return chatbot_clean_text($reply . '. ' . $ending);
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
            '/\b(dead|death|died|has died|cardiac arrest|first aid|medical first aid)\b/i',
            '/\b(namatay|patay|walang pulso|first aid)\b/i',
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
        '/\b(dead|death|died|has died|cardiac arrest|first aid|needs first aid)\b/i',
        '/\b(namatay|patay|walang pulso|kailangan ng first aid)\b/i',
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

/**
 * @param mixed $default
 * @return mixed
 */
function chatbot_cfg(string $key, $default = null) {
    if (function_exists('getSecureConfig')) {
        return getSecureConfig($key, $default);
    }
    $env = getenv($key);
    if ($env !== false && trim((string)$env) !== '') {
        return $env;
    }
    return $default;
}

function chatbot_trim_text(string $value, int $maxLen = 4000): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if ($maxLen < 1) {
        return '';
    }
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLen, 'UTF-8');
    }
    return substr($value, 0, $maxLen);
}

function chatbot_neon_log_enabled(): bool {
    return chatbot_to_bool(chatbot_cfg('CHATBOT_NEON_LOG_ENABLED', true));
}

function chatbot_neon_url(): string {
    $candidates = [
        trim((string)chatbot_cfg('CHATBOT_NEON_URL', '')),
        trim((string)chatbot_cfg('NEON_CHATBOT_URL', '')),
        trim((string)chatbot_cfg('NEON_DATABASE_URL', '')),
        trim((string)chatbot_cfg('NEON_TRANSLATION_CACHE_URL', '')),
        trim((string)chatbot_cfg('PG_IMG_URL', '')),
    ];
    foreach ($candidates as $candidate) {
        if ($candidate !== '') {
            return $candidate;
        }
    }
    return '';
}

function chatbot_neon_log_table(): string {
    $raw = trim((string)chatbot_cfg('CHATBOT_NEON_TABLE', 'chatbot_interactions'));
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $raw);
    return $table !== '' ? $table : 'chatbot_interactions';
}

function chatbot_neon_pdo(): ?PDO {
    static $attempted = false;
    static $pdo = null;

    if ($attempted) {
        return $pdo;
    }
    $attempted = true;

    if (!extension_loaded('pdo_pgsql')) {
        error_log('chatbot-assistant: Neon logging disabled (pdo_pgsql extension missing)');
        return null;
    }

    $host = trim((string)chatbot_cfg('CHATBOT_NEON_HOST', chatbot_cfg('PG_IMG_HOST', '')));
    $port = (int)chatbot_cfg('CHATBOT_NEON_PORT', chatbot_cfg('PG_IMG_PORT', 5432));
    $dbName = trim((string)chatbot_cfg('CHATBOT_NEON_DB', chatbot_cfg('PG_IMG_DB', '')));
    $user = trim((string)chatbot_cfg('CHATBOT_NEON_USER', chatbot_cfg('PG_IMG_USER', '')));
    $pass = (string)chatbot_cfg('CHATBOT_NEON_PASS', chatbot_cfg('PG_IMG_PASS', ''));
    $sslmode = trim((string)chatbot_cfg('CHATBOT_NEON_SSLMODE', chatbot_cfg('PG_IMG_SSLMODE', 'require')));
    $channelBinding = trim((string)chatbot_cfg('CHATBOT_NEON_CHANNEL_BINDING', chatbot_cfg('PG_IMG_CHANNEL_BINDING', '')));
    $libpqOptions = trim((string)chatbot_cfg('CHATBOT_NEON_OPTIONS', chatbot_cfg('PG_IMG_OPTIONS', '')));

    $url = chatbot_neon_url();
    if ($url !== '') {
        $parts = @parse_url($url);
        if (is_array($parts)) {
            if (!empty($parts['host'])) {
                $host = (string)$parts['host'];
            }
            if (!empty($parts['port'])) {
                $port = (int)$parts['port'];
            }
            if (!empty($parts['path'])) {
                $dbName = ltrim((string)$parts['path'], '/');
            }
            if (isset($parts['user'])) {
                $user = rawurldecode((string)$parts['user']);
            }
            if (isset($parts['pass'])) {
                $pass = rawurldecode((string)$parts['pass']);
            }
            if (!empty($parts['query'])) {
                $query = [];
                parse_str((string)$parts['query'], $query);
                if (!empty($query['sslmode'])) {
                    $sslmode = (string)$query['sslmode'];
                }
                if (!empty($query['channel_binding'])) {
                    $channelBinding = (string)$query['channel_binding'];
                }
                if (!empty($query['options'])) {
                    $libpqOptions = (string)$query['options'];
                }
            }
        }
    } elseif (function_exists('twc_postgres_image_pdo')) {
        $fallbackPdo = twc_postgres_image_pdo();
        if ($fallbackPdo instanceof PDO) {
            $pdo = $fallbackPdo;
            return $pdo;
        }
    }

    if ($host === '' || $dbName === '' || $user === '') {
        error_log('chatbot-assistant: Neon logging config incomplete (host/db/user missing)');
        return null;
    }

    if ($libpqOptions === '' && stripos($host, '-pooler.') !== false) {
        $labels = explode('.', $host);
        $endpointLabel = trim((string)($labels[0] ?? ''));
        if ($endpointLabel !== '') {
            $libpqOptions = 'endpoint=' . $endpointLabel;
        }
    }

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbName};sslmode={$sslmode}";
    if ($libpqOptions !== '') {
        $dsn .= ';options=' . $libpqOptions;
    }

    $normalizedChannelBinding = strtolower($channelBinding);
    $dsnWithChannelBinding = $dsn;
    if (in_array($normalizedChannelBinding, ['require', 'prefer', 'disable'], true)) {
        $dsnWithChannelBinding .= ';channel_binding=' . $normalizedChannelBinding;
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10,
    ];

    try {
        $pdo = new PDO($dsnWithChannelBinding, $user, $pass, $options);
        return $pdo;
    } catch (Throwable $e) {
        if (
            $dsnWithChannelBinding !== $dsn &&
            stripos($e->getMessage(), 'invalid connection option "channel_binding"') !== false
        ) {
            try {
                $pdo = new PDO($dsn, $user, $pass, $options);
                return $pdo;
            } catch (Throwable $retryError) {
                error_log('chatbot-assistant: Neon logging retry without channel_binding failed: ' . $retryError->getMessage());
                return null;
            }
        }

        error_log('chatbot-assistant: Neon logging connection failed: ' . $e->getMessage());
        return null;
    }
}

function chatbot_neon_log_table_ready(): bool {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }

    $pg = chatbot_neon_pdo();
    if (!$pg) {
        $ready = false;
        return false;
    }

    $table = chatbot_neon_log_table();
    try {
        $pg->exec("
            CREATE TABLE IF NOT EXISTS {$table} (
                id BIGSERIAL PRIMARY KEY,
                session_key VARCHAR(120) NULL,
                user_id VARCHAR(120) NULL,
                conversation_id VARCHAR(80) NULL,
                request_text TEXT NOT NULL,
                response_text TEXT NOT NULL,
                incident_type VARCHAR(64) NULL,
                incident_label VARCHAR(120) NULL,
                emergency_detected BOOLEAN NOT NULL DEFAULT FALSE,
                language_code VARCHAR(16) NULL,
                locale VARCHAR(40) NULL,
                model_used VARCHAR(80) NULL,
                used_rule_fallback BOOLEAN NOT NULL DEFAULT FALSE,
                qc_scope VARCHAR(24) NULL,
                qc_barangays TEXT NULL,
                metadata JSONB NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            )
        ");
        $pg->exec("CREATE INDEX IF NOT EXISTS idx_{$table}_created_at ON {$table}(created_at DESC)");
        $pg->exec("CREATE INDEX IF NOT EXISTS idx_{$table}_conversation_id ON {$table}(conversation_id)");
        $ready = true;
        return true;
    } catch (Throwable $e) {
        $ready = false;
        error_log('chatbot-assistant: Neon log table ensure failed: ' . $e->getMessage());
        return false;
    }
}

function chatbot_log_interaction(array $payload): bool {
    if (!chatbot_neon_log_enabled()) {
        return false;
    }
    if (!chatbot_neon_log_table_ready()) {
        return false;
    }

    $pg = chatbot_neon_pdo();
    if (!$pg) {
        return false;
    }

    $table = chatbot_neon_log_table();

    $sessionKey = chatbot_trim_text((string)($payload['session_key'] ?? ''), 120);
    $userId = chatbot_trim_text((string)($payload['user_id'] ?? ''), 120);
    $conversationId = chatbot_trim_text((string)($payload['conversation_id'] ?? ''), 80);
    $requestText = chatbot_trim_text((string)($payload['request_text'] ?? ''), 7000);
    $responseText = chatbot_trim_text((string)($payload['response_text'] ?? ''), 7000);
    $incidentType = chatbot_trim_text((string)($payload['incident_type'] ?? ''), 64);
    $incidentLabel = chatbot_trim_text((string)($payload['incident_label'] ?? ''), 120);
    $emergencyDetected = !empty($payload['emergency_detected']);
    $languageCode = chatbot_trim_text((string)($payload['language_code'] ?? ''), 16);
    $locale = chatbot_trim_text((string)($payload['locale'] ?? ''), 40);
    $modelUsed = chatbot_trim_text((string)($payload['model_used'] ?? ''), 80);
    $usedRuleFallback = !empty($payload['used_rule_fallback']);
    $qcScope = chatbot_trim_text((string)($payload['qc_scope'] ?? ''), 24);
    $qcBarangays = $payload['qc_barangays'] ?? [];
    if (is_array($qcBarangays)) {
        $qcBarangays = implode(', ', array_slice(array_values(array_filter(array_map('trim', $qcBarangays))), 0, 20));
    }
    $qcBarangays = chatbot_trim_text((string)$qcBarangays, 1200);
    $metadata = $payload['metadata'] ?? null;

    if ($requestText === '' || $responseText === '') {
        return false;
    }

    $metadataJson = null;
    if (is_array($metadata) || is_object($metadata)) {
        $encoded = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_string($encoded) && $encoded !== '' && $encoded !== 'null') {
            $metadataJson = $encoded;
        }
    } elseif (is_string($metadata) && trim($metadata) !== '') {
        $candidate = chatbot_trim_text($metadata, 4000);
        $decoded = json_decode($candidate, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $metadataJson = $candidate;
        } else {
            $metadataJson = json_encode(
                ['raw' => $candidate],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }
    }

    try {
        $stmt = $pg->prepare("
            INSERT INTO {$table}
                (session_key, user_id, conversation_id, request_text, response_text,
                 incident_type, incident_label, emergency_detected, language_code, locale,
                 model_used, used_rule_fallback, qc_scope, qc_barangays, metadata, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CAST(? AS JSONB), NOW())
        ");
        $stmt->execute([
            $sessionKey !== '' ? $sessionKey : null,
            $userId !== '' ? $userId : null,
            $conversationId !== '' ? $conversationId : null,
            $requestText,
            $responseText,
            $incidentType !== '' ? $incidentType : null,
            $incidentLabel !== '' ? $incidentLabel : null,
            $emergencyDetected ? 1 : 0,
            $languageCode !== '' ? $languageCode : null,
            $locale !== '' ? $locale : null,
            $modelUsed !== '' ? $modelUsed : null,
            $usedRuleFallback ? 1 : 0,
            $qcScope !== '' ? $qcScope : null,
            $qcBarangays !== '' ? $qcBarangays : null,
            $metadataJson,
        ]);
        return true;
    } catch (Throwable $e) {
        error_log('chatbot-assistant: Neon log insert failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * @return string[]
 */
function chatbot_qc_barangays(): array {
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }

    $fallback = [
        'Commonwealth',
        'Batasan Hills',
        'Tandang Sora',
        'Bagong Pag-asa',
        'Holy Spirit',
        'Payatas',
        'Novaliches Proper',
        'Culiat',
        'Pasong Tamo',
        'Loyola Heights',
    ];

    $path = dirname(__DIR__, 2) . '/barangay-main/barangay/data/qc_barangays.json';
    if (!is_file($path)) {
        $cached = $fallback;
        return $cached;
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        $cached = $fallback;
        return $cached;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $cached = $fallback;
        return $cached;
    }

    $list = [];
    foreach ($decoded as $item) {
        $name = trim((string)$item);
        if ($name !== '') {
            $list[$name] = true;
        }
    }

    $cached = !empty($list) ? array_keys($list) : $fallback;
    return $cached;
}

function chatbot_qc_normalize_token(string $text): string {
    $text = strtolower(trim($text));
    $text = str_replace(['ñ', 'Ñ'], 'n', $text);
    $text = preg_replace('/[^a-z0-9]+/u', ' ', $text);
    $text = preg_replace('/\s+/', ' ', (string)$text);
    return trim((string)$text);
}

/**
 * @param string[] $barangays
 * @return string[]
 */
function chatbot_match_qc_barangays(string $text, array $barangays): array {
    $normalizedHaystack = chatbot_qc_normalize_token($text);
    if ($normalizedHaystack === '') {
        return [];
    }

    $searchable = ' ' . $normalizedHaystack . ' ';
    $matches = [];
    foreach ($barangays as $barangay) {
        $normalizedBarangay = chatbot_qc_normalize_token((string)$barangay);
        if ($normalizedBarangay === '') {
            continue;
        }
        if (strpos($searchable, ' ' . $normalizedBarangay . ' ') !== false) {
            $matches[] = (string)$barangay;
            if (count($matches) >= 8) {
                break;
            }
        }
    }

    return $matches;
}

function chatbot_detect_non_qc_location(string $text): string {
    $normalized = chatbot_normalize_for_match($text);
    if ($normalized === '') {
        return '';
    }

    $patterns = [
        'Makati' => '/\bmakati\b/i',
        'Manila' => '/\bmanila\b/i',
        'Taguig' => '/\btaguig\b/i',
        'Pasig' => '/\bpasig\b/i',
        'Mandaluyong' => '/\bmandaluyong\b/i',
        'Caloocan' => '/\bcaloocan\b/i',
        'Marikina' => '/\bmarikina\b/i',
        'San Juan' => '/\bsan juan\b/i',
        'Paranaque' => '/\bparanaque|parañaque\b/i',
        'Las Pinas' => '/\blas pinas|las piñas\b/i',
        'Pasay' => '/\bpasay\b/i',
        'Quezon Province' => '/\bquezon province\b/i',
    ];

    foreach ($patterns as $label => $pattern) {
        if (preg_match($pattern, $normalized) === 1) {
            return $label;
        }
    }

    return '';
}

/**
 * @param string[] $matchedBarangays
 */
function chatbot_qc_scope(string $sourceText, array $matchedBarangays, string $nonQcLocation): string {
    if ($nonQcLocation !== '') {
        return 'outside_qc';
    }
    if (!empty($matchedBarangays) || preg_match('/\b(quezon city|qc)\b/i', $sourceText) === 1) {
        return 'qc';
    }
    return 'unknown';
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

function chatbot_mysql_pdo(): ?PDO {
    static $attempted = false;
    static $mysqlPdo = null;

    if ($attempted) {
        return $mysqlPdo;
    }
    $attempted = true;

    try {
        require __DIR__ . '/../../ADMIN/api/db_connect.php';
        if (isset($pdo) && $pdo instanceof PDO) {
            $mysqlPdo = $pdo;
        }
    } catch (Throwable $e) {
        error_log('chatbot-assistant: MySQL bootstrap failed: ' . $e->getMessage());
    }

    return $mysqlPdo;
}

function chatbot_guest_user_id(): string {
    try {
        return 'guest_' . date('YmdHis') . '_' . substr(bin2hex(random_bytes(6)), 0, 12);
    } catch (Throwable $e) {
        return 'guest_' . date('YmdHis') . '_' . substr(md5((string)microtime(true)), 0, 12);
    }
}

function chatbot_resolve_user_context(array $input): array {
    $userId = trim((string)($input['userId'] ?? ''));
    if ($userId === '') {
        $userId = chatbot_guest_user_id();
    }

    $hasIsGuestInput = array_key_exists('isGuest', $input);
    $isGuest = $hasIsGuestInput
        ? chatbot_to_bool($input['isGuest'])
        : (stripos($userId, 'guest_') === 0 || preg_match('/^\d+$/', $userId) !== 1);

    $userName = trim((string)($input['userName'] ?? 'Guest User'));
    if ($userName === '') {
        $userName = 'Guest User';
    }

    $conversationId = twc_safe_int($input['conversationId'] ?? null);
    if ($conversationId !== null && $conversationId <= 0) {
        $conversationId = null;
    }

    $ipAddress = function_exists('getClientIP')
        ? (string)getClientIP()
        : (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $deviceInfo = function_exists('formatDeviceInfoForDB')
        ? (string)formatDeviceInfoForDB()
        : '';
    if (trim($deviceInfo) === '') {
        $deviceInfo = null;
    }

    return [
        'userId' => $userId,
        'userName' => $userName,
        'userEmail' => trim((string)($input['userEmail'] ?? '')),
        'userPhone' => trim((string)($input['userPhone'] ?? '')),
        'userLocation' => trim((string)($input['userLocation'] ?? '')),
        'userConcern' => trim((string)($input['userConcern'] ?? 'chatbot_assistant')),
        'isGuest' => $isGuest,
        'conversationId' => $conversationId,
        'ipAddress' => $ipAddress,
        'deviceInfo' => $deviceInfo,
        'userAgent' => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
    ];
}

function chatbot_insert_chat_message(PDO $pdo, array $payload): ?int {
    $conversationId = twc_safe_int($payload['conversationId'] ?? null);
    $senderId = trim((string)($payload['senderId'] ?? ''));
    $senderName = trim((string)($payload['senderName'] ?? 'Citizen'));
    $senderType = strtolower(trim((string)($payload['senderType'] ?? 'user')));
    $messageText = trim((string)($payload['messageText'] ?? ''));
    $isRead = !empty($payload['isRead']) ? 1 : 0;

    if ($conversationId === null || $conversationId <= 0 || $messageText === '') {
        return null;
    }

    if (!in_array($senderType, ['user', 'admin'], true)) {
        $senderType = 'user';
    }

    $columns = [
        'conversation_id',
        'sender_id',
        'sender_name',
        'sender_type',
        'message_text',
        'is_read',
        'created_at',
    ];
    $values = [
        $conversationId,
        $senderId,
        $senderName !== '' ? $senderName : 'Citizen',
        $senderType,
        $messageText,
        $isRead,
        date('Y-m-d H:i:s'),
    ];

    if (twc_column_exists($pdo, 'chat_messages', 'ip_address')) {
        $columns[] = 'ip_address';
        $values[] = trim((string)($payload['ipAddress'] ?? '')) ?: null;
    }
    if (twc_column_exists($pdo, 'chat_messages', 'device_info')) {
        $columns[] = 'device_info';
        $values[] = trim((string)($payload['deviceInfo'] ?? '')) ?: null;
    }

    $sql = "INSERT INTO chat_messages (" . implode(', ', $columns) . ")
            VALUES (" . twc_placeholders($values) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    $rawId = $pdo->lastInsertId();
    return is_numeric($rawId) ? (int)$rawId : null;
}

/**
 * Routes chatbot emergency reports into admin two-way conversation queue.
 *
 * @return array{
 *   routed:bool,
 *   reason:string,
 *   conversationId:int|null,
 *   messageId:int|null,
 *   createdConversation:bool,
 *   assignedTo:int|null,
 *   riskLevel:string,
 *   riskNotification:array|null
 * }
 */
function chatbot_route_emergency_to_admin(array $context): array {
    $result = [
        'routed' => false,
        'reason' => 'not_attempted',
        'conversationId' => null,
        'messageId' => null,
        'createdConversation' => false,
        'assignedTo' => null,
        'riskLevel' => 'critical',
        'riskNotification' => null,
    ];

    $message = trim((string)($context['message'] ?? ''));
    if ($message === '') {
        $result['reason'] = 'empty_message';
        return $result;
    }

    $pdo = chatbot_mysql_pdo();
    if (!$pdo instanceof PDO) {
        $result['reason'] = 'mysql_unavailable';
        return $result;
    }
    if (!twc_chat_storage_available($pdo)) {
        $result['reason'] = 'chat_storage_unavailable';
        return $result;
    }

    $userId = trim((string)($context['userId'] ?? ''));
    if ($userId === '') {
        $userId = chatbot_guest_user_id();
    }
    $userName = trim((string)($context['userName'] ?? 'Guest User'));
    if ($userName === '') {
        $userName = 'Guest User';
    }
    $userEmail = trim((string)($context['userEmail'] ?? ''));
    $userPhone = trim((string)($context['userPhone'] ?? ''));
    $userLocation = trim((string)($context['userLocation'] ?? ''));
    $isGuest = !empty($context['isGuest']);
    $ipAddress = trim((string)($context['ipAddress'] ?? ''));
    $deviceInfo = trim((string)($context['deviceInfo'] ?? ''));
    $userAgent = trim((string)($context['userAgent'] ?? ''));

    $category = twc_normalize_category('emergency_response');
    if ($category === '') {
        $category = 'emergency_response';
    }
    $priority = 'urgent';
    $riskLevel = twc_chat_risk_level($message, $category, $priority);
    if ($riskLevel === 'normal') {
        $riskLevel = 'high';
    }
    $result['riskLevel'] = $riskLevel;

    $hasCategoryColumn = twc_column_exists($pdo, 'conversations', 'category');
    $hasPriorityColumn = twc_column_exists($pdo, 'conversations', 'priority');
    $hasAssignedToColumn = twc_column_exists($pdo, 'conversations', 'assigned_to');
    $hasUserIdStringColumn = twc_column_exists($pdo, 'conversations', 'user_id_string');
    $hasDeviceInfoColumn = twc_column_exists($pdo, 'conversations', 'device_info');
    $hasIpColumn = twc_column_exists($pdo, 'conversations', 'ip_address');
    $hasUserAgentColumn = twc_column_exists($pdo, 'conversations', 'user_agent');
    $assignedSelect = $hasAssignedToColumn ? 'assigned_to' : 'NULL AS assigned_to';

    $conversationId = twc_safe_int($context['conversationId'] ?? null);
    if ($conversationId !== null && $conversationId <= 0) {
        $conversationId = null;
    }
    $assignedTo = null;
    $createdConversation = false;

    try {
        $pdo->beginTransaction();

        if ($conversationId !== null) {
            $existingStmt = $pdo->prepare("
                SELECT conversation_id, status, {$assignedSelect}
                FROM conversations
                WHERE conversation_id = ?
                LIMIT 1
            ");
            $existingStmt->execute([$conversationId]);
            $existingRow = $existingStmt->fetch(PDO::FETCH_ASSOC);
            if (!$existingRow || twc_is_closed_status($existingRow['status'] ?? '')) {
                $conversationId = null;
            } else {
                $assignedTo = twc_safe_int($existingRow['assigned_to'] ?? null);
            }
        }

        if ($conversationId === null) {
            $activeStatuses = twc_active_statuses();
            $activeIn = twc_placeholders($activeStatuses);
            $existingRow = null;

            if ($hasUserIdStringColumn && !is_numeric($userId)) {
                $sql = "
                    SELECT conversation_id, {$assignedSelect}
                    FROM conversations
                    WHERE user_id_string = ?
                      AND status IN ($activeIn)
                    ORDER BY updated_at DESC, conversation_id DESC
                    LIMIT 1
                ";
                $params = array_merge([$userId], $activeStatuses);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $existingRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } elseif (is_numeric($userId)) {
                $sql = "
                    SELECT conversation_id, {$assignedSelect}
                    FROM conversations
                    WHERE user_id = ?
                      AND status IN ($activeIn)
                    ORDER BY updated_at DESC, conversation_id DESC
                    LIMIT 1
                ";
                $params = array_merge([(int)$userId], $activeStatuses);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $existingRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            if (
                !$existingRow &&
                $isGuest &&
                $hasDeviceInfoColumn &&
                $hasIpColumn &&
                $ipAddress !== '' &&
                $deviceInfo !== ''
            ) {
                $sql = "
                    SELECT conversation_id, {$assignedSelect}
                    FROM conversations
                    WHERE ip_address = ?
                      AND device_info = ?
                      AND status IN ($activeIn)
                    ORDER BY updated_at DESC, conversation_id DESC
                    LIMIT 1
                ";
                $params = array_merge([$ipAddress, $deviceInfo], $activeStatuses);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $existingRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            if ($existingRow) {
                $conversationId = (int)$existingRow['conversation_id'];
                $assignedTo = twc_safe_int($existingRow['assigned_to'] ?? null);
            } else {
                $createdConversation = true;
                $assignedTo = $hasAssignedToColumn ? twc_pick_assignee($pdo) : null;
                $statusOpen = twc_status_for_db($pdo, 'open');

                $columns = [
                    'user_id',
                    'user_name',
                    'user_email',
                    'user_phone',
                    'user_location',
                    'user_concern',
                    'is_guest',
                    'status',
                    'created_at',
                    'updated_at',
                ];
                $values = [
                    is_numeric($userId) ? (int)$userId : 0,
                    $userName,
                    $userEmail !== '' ? $userEmail : null,
                    $userPhone !== '' ? $userPhone : null,
                    $userLocation !== '' ? $userLocation : null,
                    $category,
                    $isGuest ? 1 : 0,
                    $statusOpen,
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                ];

                if ($hasUserIdStringColumn) {
                    $columns[] = 'user_id_string';
                    $values[] = is_numeric($userId) ? null : $userId;
                }
                if ($hasDeviceInfoColumn) {
                    $columns[] = 'device_info';
                    $values[] = $deviceInfo !== '' ? $deviceInfo : null;
                }
                if ($hasIpColumn) {
                    $columns[] = 'ip_address';
                    $values[] = $ipAddress !== '' ? $ipAddress : null;
                }
                if ($hasUserAgentColumn) {
                    $columns[] = 'user_agent';
                    $values[] = $userAgent !== '' ? $userAgent : null;
                }
                if ($hasAssignedToColumn && $assignedTo !== null) {
                    $columns[] = 'assigned_to';
                    $values[] = $assignedTo;
                }
                if ($hasCategoryColumn) {
                    $columns[] = 'category';
                    $values[] = $category;
                }
                if ($hasPriorityColumn) {
                    $columns[] = 'priority';
                    $values[] = $priority;
                }

                $insertSql = "INSERT INTO conversations (" . implode(',', $columns) . ")
                              VALUES (" . twc_placeholders($values) . ")";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute($values);
                $conversationId = (int)$pdo->lastInsertId();
            }
        }

        if ($conversationId === null || $conversationId <= 0) {
            throw new RuntimeException('Unable to resolve conversation for emergency routing.');
        }

        $messageId = null;
        try {
            $dedupeStmt = $pdo->prepare("
                SELECT message_id
                FROM chat_messages
                WHERE conversation_id = ?
                  AND sender_type = 'user'
                  AND sender_id = ?
                  AND message_text = ?
                  AND created_at >= (NOW() - INTERVAL 20 SECOND)
                ORDER BY message_id DESC
                LIMIT 1
            ");
            $dedupeStmt->execute([$conversationId, $userId, $message]);
            $existingMessageId = $dedupeStmt->fetchColumn();
            if ($existingMessageId) {
                $messageId = (int)$existingMessageId;
            }
        } catch (Throwable $e) {
            // Non-blocking dedupe guard.
        }

        if ($messageId === null) {
            $messageId = chatbot_insert_chat_message($pdo, [
                'conversationId' => $conversationId,
                'senderId' => $userId,
                'senderName' => $userName,
                'senderType' => 'user',
                'messageText' => $message,
                'isRead' => false,
                'ipAddress' => $ipAddress,
                'deviceInfo' => $deviceInfo,
            ]);
        }

        $statusInProgress = twc_status_for_db($pdo, 'in_progress');
        $updateParts = [
            'last_message = ?',
            'last_message_time = NOW()',
            'updated_at = NOW()',
            'status = ?',
            'user_concern = ?',
        ];
        $updateParams = [$message, $statusInProgress, $category];

        if ($hasCategoryColumn) {
            $updateParts[] = 'category = ?';
            $updateParams[] = $category;
        }
        if ($hasPriorityColumn) {
            $updateParts[] = 'priority = ?';
            $updateParams[] = $priority;
        }
        if ($hasAssignedToColumn) {
            if ($assignedTo === null) {
                $assignedTo = twc_pick_assignee($pdo);
            }
            if ($assignedTo !== null) {
                $updateParts[] = 'assigned_to = ?';
                $updateParams[] = $assignedTo;
            }
        }

        $updateParams[] = $conversationId;
        $updateSql = "UPDATE conversations SET " . implode(', ', $updateParts) . " WHERE conversation_id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute($updateParams);

        if (twc_table_exists($pdo, 'chat_queue')) {
            $queueHasAssigned = twc_column_exists($pdo, 'chat_queue', 'assigned_to');
            $queueColumns = [
                'conversation_id',
                'user_id',
                'user_name',
                'user_email',
                'user_phone',
                'user_location',
                'user_concern',
                'is_guest',
                'message',
                'status',
                'created_at',
            ];
            $queueValues = [
                $conversationId,
                $userId,
                $userName,
                $userEmail !== '' ? $userEmail : null,
                $userPhone !== '' ? $userPhone : null,
                $userLocation !== '' ? $userLocation : null,
                $category,
                $isGuest ? 1 : 0,
                $message,
                'pending',
                date('Y-m-d H:i:s'),
            ];
            if ($queueHasAssigned) {
                $queueColumns[] = 'assigned_to';
                $queueValues[] = $assignedTo;
            }

            $queueSql = "INSERT INTO chat_queue (" . implode(',', $queueColumns) . ")
                         VALUES (" . twc_placeholders($queueValues) . ")
                         ON DUPLICATE KEY UPDATE
                            message = VALUES(message),
                            status = 'pending',
                            updated_at = NOW()";
            if ($queueHasAssigned) {
                $queueSql .= ", assigned_to = VALUES(assigned_to)";
            }
            $queueStmt = $pdo->prepare($queueSql);
            $queueStmt->execute($queueValues);
        }

        $pdo->commit();

        $riskNotification = twc_emit_chat_risk_notification($pdo, [
            'message' => $message,
            'category' => $category,
            'priority' => $priority,
            'riskLevel' => $riskLevel,
            'conversationId' => $conversationId,
            'userName' => $userName,
            'userLocation' => $userLocation,
            'assignedTo' => $assignedTo,
            'ipAddress' => $ipAddress,
            'snippet' => $message,
        ]);

        $result['routed'] = true;
        $result['reason'] = 'routed';
        $result['conversationId'] = $conversationId;
        $result['messageId'] = $messageId;
        $result['createdConversation'] = $createdConversation;
        $result['assignedTo'] = $assignedTo;
        $result['riskNotification'] = $riskNotification;
        return $result;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('chatbot-assistant emergency routing failed: ' . $e->getMessage());
        $result['reason'] = 'route_failed';
        return $result;
    }
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
    $userContext = chatbot_resolve_user_context($input);

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
        . "- Always finish with complete sentences and proper punctuation.\n"
        . "- Match the user's language. If user writes in Filipino/Tagalog, reply in Filipino/Tagalog.\n"
        . "- Focus on Quezon City locations, barangays, and emergency context.\n"
        . "- If the user location looks outside Quezon City, ask for confirmation and remind them to contact local responders.\n"
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

    $routingText = trim($message . ' ' . implode(' ', array_slice($historyUserMessages, -4)));
    $qcBarangays = chatbot_qc_barangays();
    $matchedQcBarangays = chatbot_match_qc_barangays($routingText, $qcBarangays);
    $nonQcLocation = chatbot_detect_non_qc_location($routingText);
    $locationScope = chatbot_qc_scope($routingText, $matchedQcBarangays, $nonQcLocation);
    $qcBarangayPromptReference = implode(', ', array_slice($qcBarangays, 0, 50));

    $prompt = $systemPrompt . "\n\n"
        . "Server routing context:\n"
        . "- Service location: Quezon City, Philippines.\n"
        . "- Service scope: Quezon City operations only.\n"
        . "- location_scope_signal: " . $locationScope . "\n"
        . "- matched_qc_barangays: " . (!empty($matchedQcBarangays) ? implode(', ', $matchedQcBarangays) : 'none_detected') . "\n"
        . "- detected_non_qc_location: " . ($nonQcLocation !== '' ? $nonQcLocation : 'none_detected') . "\n"
        . "- qc_barangay_reference_sample: " . $qcBarangayPromptReference . "\n"
        . "- emergency_detected: " . ($isEmergency ? 'true' : 'false') . "\n"
        . "- incident_type: " . $incidentType . "\n"
        . "- incident_label: " . $incidentLabel . "\n"
        . "- response_language_code: " . $preferredLanguage . "\n"
        . "- response_language_name: " . $preferredLanguageName . "\n"
        . "- emergency_number: " . $emergencyNumber . "\n"
        . "- emergency_call_link: " . ($callLink !== '' ? $callLink : 'not_configured') . "\n"
        . "- Always reply in response_language_name.\n"
        . "- If location_scope_signal=outside_qc: ask user to confirm exact Quezon City barangay and explain that this assistant is optimized for QC routing.\n"
        . "- If location_scope_signal=outside_qc and incident is urgent: still give immediate safety steps, then advise contacting local emergency responders in that city.\n"
        . "- Ask for barangay + landmark (street, nearest school/hospital/intersection) whenever location details are incomplete.\n"
        . "- If response_language_code=fil, use natural Filipino (Tagalog). Avoid switching to English except URLs, numbers, and proper names.\n"
        . "- If emergency_detected=true and response_language_code=en: include 'Call " . $emergencyNumber . " immediately if life is at risk.'\n"
        . "- If emergency_detected=true and response_language_code=fil: include 'Tumawag agad sa " . $emergencyNumber . " kung may banta sa buhay.'\n"
        . "- If emergency_detected=true: include the emergency_call_link exactly once.\n"
        . "- If user asks for classification (e.g., 'what incident is this'), answer with one category from:\n"
        . "  fire, medical_emergency, crime_violence, road_accident, flood, earthquake, landslide, typhoon_storm, electrical_hazard, missing_person, rescue_request, general_support.\n"
        . "- Follow-up hint for this case: " . $followUpHint . "\n"
        . "- End your answer with a complete final sentence.\n"
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

    $reply = chatbot_finalize_reply($reply, $preferredLanguage, $followUpHint);
    $reply = chatbot_limit_reply_length($reply, 1800);

    $adminRouting = [
        'routed' => false,
        'reason' => $isEmergency ? 'not_routed' : 'not_emergency',
        'conversationId' => null,
        'messageId' => null,
        'createdConversation' => false,
        'assignedTo' => null,
        'riskLevel' => $isEmergency ? 'high' : 'normal',
        'riskNotification' => null,
    ];
    if ($isEmergency) {
        $adminRouting = chatbot_route_emergency_to_admin([
            'message' => $message,
            'reply' => $reply,
            'incidentType' => $incidentType,
            'incidentLabel' => $incidentLabel,
            'languageCode' => $preferredLanguage,
            'userId' => (string)($userContext['userId'] ?? ''),
            'userName' => (string)($userContext['userName'] ?? ''),
            'userEmail' => (string)($userContext['userEmail'] ?? ''),
            'userPhone' => (string)($userContext['userPhone'] ?? ''),
            'userLocation' => (string)($userContext['userLocation'] ?? ''),
            'isGuest' => !empty($userContext['isGuest']),
            'conversationId' => $userContext['conversationId'] ?? null,
            'ipAddress' => (string)($userContext['ipAddress'] ?? ''),
            'deviceInfo' => (string)($userContext['deviceInfo'] ?? ''),
            'userAgent' => (string)($userContext['userAgent'] ?? ''),
        ]);
    }

    $resolvedConversationIdForLog = '';
    if (!empty($adminRouting['conversationId'])) {
        $resolvedConversationIdForLog = (string)$adminRouting['conversationId'];
    } elseif (!empty($userContext['conversationId'])) {
        $resolvedConversationIdForLog = (string)$userContext['conversationId'];
    } elseif (!empty($input['conversationId'])) {
        $resolvedConversationIdForLog = (string)$input['conversationId'];
    }

    $replyLanguageDetected = chatbot_detect_language_from_text($reply);
    $resolvedModel = $usedRuleFallback ? 'rule-fallback' : $configuredModel;
    $loggedToNeon = chatbot_log_interaction([
        'session_key' => (string)($input['sessionId'] ?? $input['sessionKey'] ?? ''),
        'user_id' => (string)($userContext['userId'] ?? ''),
        'conversation_id' => $resolvedConversationIdForLog,
        'request_text' => $message,
        'response_text' => $reply,
        'incident_type' => $incidentType,
        'incident_label' => $incidentLabel,
        'emergency_detected' => $isEmergency,
        'language_code' => $preferredLanguage,
        'locale' => $locale,
        'model_used' => $resolvedModel,
        'used_rule_fallback' => $usedRuleFallback,
        'qc_scope' => $locationScope,
        'qc_barangays' => $matchedQcBarangays,
        'metadata' => [
            'reply_language_detected' => $replyLanguageDetected,
            'non_qc_location' => $nonQcLocation,
            'history_count' => count($historyLines),
            'emergency_number' => $emergencyNumber,
            'call_link' => $callLink,
            'admin_routing' => $adminRouting,
        ],
    ]);

    echo json_encode([
        'success' => true,
        'reply' => $reply,
        'model' => $resolvedModel,
        'timestamp' => round(microtime(true) * 1000),
        'emergencyDetected' => $isEmergency,
        'incidentType' => $incidentType,
        'incidentLabel' => $incidentLabel,
        'emergencyNumber' => $emergencyNumber,
        'callLink' => $callLink,
        'usedRuleFallback' => $usedRuleFallback,
        'preferredLanguage' => $preferredLanguage,
        'replyLanguage' => $replyLanguageDetected,
        'locationScope' => $locationScope,
        'matchedQcBarangays' => $matchedQcBarangays,
        'loggedToNeon' => $loggedToNeon,
        'conversationId' => $resolvedConversationIdForLog,
        'adminRouting' => $adminRouting,
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
