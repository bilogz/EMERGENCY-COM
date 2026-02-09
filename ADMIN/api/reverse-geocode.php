<?php
header('Content-Type: application/json; charset=utf-8');

$lat = isset($_GET['lat']) ? $_GET['lat'] : null;
$lng = isset($_GET['lng']) ? $_GET['lng'] : null;

if ($lat === null || $lng === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing lat/lng']);
    exit;
}

if (!is_numeric($lat) || !is_numeric($lng)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid lat/lng']);
    exit;
}

$lat = floatval($lat);
$lng = floatval($lng);

$url = 'https://nominatim.openstreetmap.org/reverse?format=json&zoom=18&addressdetails=1'
    . '&lat=' . urlencode($lat) . '&lon=' . urlencode($lng);

if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: en',
        'User-Agent: EMERGENCY-COM/1.0 (local)'
    ]);

    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
} else {
    $opts = [
        'http' => [
            'method' => 'GET',
            'timeout' => 6,
            'header' => "Accept: application/json\r\nAccept-Language: en\r\nUser-Agent: EMERGENCY-COM/1.0 (local)\r\n"
        ]
    ];
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    $err = $resp === false ? 'file_get_contents failed' : '';
    $code = $resp === false ? 502 : 200;
}

if ($resp === false || $code < 200 || $code >= 300) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Reverse geocode failed', 'details' => $err]);
    exit;
}

$data = json_decode($resp, true);
$name = '';
if (is_array($data)) {
    if (!empty($data['display_name'])) $name = $data['display_name'];
    else if (!empty($data['name'])) $name = $data['name'];
}

echo json_encode([
    'success' => true,
    'address' => $name
]);
