<?php
/**
 * PHIVOLCS Earthquake Bulletin Scraper API
 * Scrapes earthquake data from https://earthquake.phivolcs.dost.gov.ph/
 * Returns structured JSON for the earthquake monitoring module.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Cache file to avoid hammering PHIVOLCS
$cacheFile = __DIR__ . '/cache/phivolcs_earthquakes.json';
$cacheDir  = __DIR__ . '/cache';
$cacheTTL  = 120; // 2 minutes cache

if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

// Return cached data if fresh enough
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
    echo file_get_contents($cacheFile);
    exit;
}

$url = 'https://earthquake.phivolcs.dost.gov.ph/';

// Use cURL for reliability
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
]);

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if (!$html || $httpCode !== 200) {
    // Try fallback with file_get_contents
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);
    $html = @file_get_contents($url, false, $context);
}

if (!$html) {
    // Return cached if available (even stale)
    if (file_exists($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached) {
            $cached['is_cached'] = true;
            $cached['cache_note'] = 'PHIVOLCS unreachable, showing cached data';
            echo json_encode($cached);
            exit;
        }
    }
    echo json_encode([
        'success' => false,
        'message' => 'Unable to reach PHIVOLCS. Error: ' . ($curlError ?: 'Connection failed'),
        'earthquakes' => []
    ]);
    exit;
}

// Parse the HTML
$earthquakes = [];

// Use DOMDocument to parse
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
libxml_clear_errors();

$tables = $dom->getElementsByTagName('table');
$dataTable = null;

// Find the data table (has header row with "Date - Time", "Latitude", etc.)
foreach ($tables as $table) {
    $ths = $table->getElementsByTagName('th');
    if ($ths->length >= 5) {
        $firstTh = trim($ths->item(0)->textContent);
        if (stripos($firstTh, 'Date') !== false || stripos($firstTh, 'Time') !== false) {
            $dataTable = $table;
            break;
        }
    }
}

if (!$dataTable) {
    // Fallback: try regex parsing
    $earthquakes = parseWithRegex($html);
} else {
    $rows = $dataTable->getElementsByTagName('tr');
    $isHeader = true;
    
    foreach ($rows as $row) {
        // Skip header row
        if ($isHeader) {
            $isHeader = false;
            continue;
        }
        
        $cells = $row->getElementsByTagName('td');
        if ($cells->length < 6) continue;
        
        $dateTimeRaw = trim($cells->item(0)->textContent);
        $latitude    = trim($cells->item(1)->textContent);
        $longitude   = trim($cells->item(2)->textContent);
        $depth       = trim($cells->item(3)->textContent);
        $magnitude   = trim($cells->item(4)->textContent);
        $location    = trim($cells->item(5)->textContent);
        
        // Extract bulletin link if available
        $links = $cells->item(0)->getElementsByTagName('a');
        $bulletinLink = '';
        if ($links->length > 0) {
            $href = $links->item(0)->getAttribute('href');
            if ($href && strpos($href, 'http') !== 0) {
                $bulletinLink = 'https://earthquake.phivolcs.dost.gov.ph/' . ltrim($href, '/');
            } else {
                $bulletinLink = $href;
            }
        }
        
        // Clean location text (remove leading distance numbers)
        $location = preg_replace('/^\d+\s*/', '', $location);
        $location = trim($location);
        
        // Parse magnitude
        $mag = floatval($magnitude);
        if ($mag <= 0) continue;
        
        // Parse coordinates
        $lat = floatval($latitude);
        $lon = floatval($longitude);
        if ($lat == 0 && $lon == 0) continue;
        
        // Parse depth
        $depthKm = intval($depth);
        
        // Clean date string
        $dateTimeClean = preg_replace('/\s+/', ' ', $dateTimeRaw);
        $dateTimeClean = trim($dateTimeClean);
        
        $earthquakes[] = [
            'date_time'     => $dateTimeClean,
            'latitude'      => $lat,
            'longitude'     => $lon,
            'depth_km'      => $depthKm,
            'magnitude'     => $mag,
            'location'      => $location,
            'bulletin_link' => $bulletinLink,
            'source'        => 'PHIVOLCS'
        ];
    }
}

$response = [
    'success'      => true,
    'source'       => 'PHIVOLCS - Philippine Institute of Volcanology and Seismology',
    'source_url'   => 'https://earthquake.phivolcs.dost.gov.ph/',
    'fetched_at'   => date('Y-m-d H:i:s T'),
    'total'        => count($earthquakes),
    'earthquakes'  => $earthquakes,
    'is_cached'    => false
];

// Cache the result
file_put_contents($cacheFile, json_encode($response));

echo json_encode($response);

/**
 * Fallback regex parser in case DOM parsing fails
 */
function parseWithRegex($html) {
    $earthquakes = [];
    
    // Match table rows with earthquake data
    // Pattern: <tr> with 6 <td> cells containing date, lat, lon, depth, mag, location
    preg_match_all('/<tr[^>]*>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>\s*([\d.]+)\s*<\/td>\s*<td[^>]*>\s*([\d.]+)\s*<\/td>\s*<td[^>]*>\s*(\d+)\s*<\/td>\s*<td[^>]*>\s*([\d.]+)\s*<\/td>\s*<td[^>]*>(.*?)<\/td>/si', $html, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $m) {
        $dateHtml = $m[1];
        $lat = floatval($m[2]);
        $lon = floatval($m[3]);
        $depth = intval($m[4]);
        $mag = floatval($m[5]);
        $locHtml = $m[6];
        
        if ($mag <= 0 || ($lat == 0 && $lon == 0)) continue;
        
        // Extract date text
        $dateText = strip_tags($dateHtml);
        $dateText = preg_replace('/\s+/', ' ', trim($dateText));
        
        // Extract bulletin link
        $bulletinLink = '';
        if (preg_match('/href=["\']([^"\']+)/', $dateHtml, $lm)) {
            $href = $lm[1];
            if (strpos($href, 'http') !== 0) {
                $bulletinLink = 'https://earthquake.phivolcs.dost.gov.ph/' . ltrim($href, '/');
            } else {
                $bulletinLink = $href;
            }
        }
        
        // Clean location
        $locText = strip_tags($locHtml);
        $locText = preg_replace('/^\d+\s*/', '', trim($locText));
        
        $earthquakes[] = [
            'date_time'     => $dateText,
            'latitude'      => $lat,
            'longitude'     => $lon,
            'depth_km'      => $depth,
            'magnitude'     => $mag,
            'location'      => trim($locText),
            'bulletin_link' => $bulletinLink,
            'source'        => 'PHIVOLCS'
        ];
    }
    
    return $earthquakes;
}
