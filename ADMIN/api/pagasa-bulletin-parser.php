<?php
/**
 * PAGASA Weather Bulletin Parser API (XML/RSS version with Directory Scraper fallback)
 * Fetches and parses active weather bulletins directly from PAGASA RSS feeds or falls back to directory index listing.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Fetch feed from PAGASA
function fetchRawPagasaFeed() {
    $urls = [
        'https://pubfiles.pagasa.dost.gov.ph/tamss/weather/bulletin.xml',
        'https://www.pagasa.dost.gov.ph/weather/bulletin-rss.xml'
    ];

    foreach ($urls as $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) EmergencyCom/1.0');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response && $httpCode === 200 && trim($response) !== '') {
            return $response;
        }
    }
    return null;
}

// Fallback mock RSS feed XML
function getMockPagasaFeedXml() {
    $pubDate = date('D, d M Y H:i:s +0800');
    return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0">
  <channel>
    <title>PAGASA Weather Bulletins</title>
    <link>https://www.pagasa.dost.gov.ph</link>
    <description>Philippine Atmospheric, Geophysical and Astronomical Services Administration</description>
    <item>
      <title>TROPICAL CYCLONE BULLETIN NR. 18 (Typhoon "MARCE")</title>
      <description>At 8:00 AM today, Typhoon "MARCE" (YINXING) was located at 175 km East of Aparri, Cagayan. Maximum sustained winds of 165 km/h and gustiness of up to 205 km/h. Moving West northwestward slowly. Southwest Monsoon enhanced by MARCE will bring moderate to heavy rainfall over Metro Manila (including Quezon City), Rizal, Cavite, and western portions of Luzon.</description>
      <pubDate>{$pubDate}</pubDate>
      <link>https://pubfiles.pagasa.dost.gov.ph/tamss/weather/bulletin.pdf</link>
    </item>
  </channel>
</rss>
XML;
}

// Directory Index Fallback Scraper
function fetchLatestFromDirectoryIndex() {
    $url = 'https://pubfiles.pagasa.dost.gov.ph/tamss/weather/bulletin/';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) EmergencyCom/1.0'
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return null;

    preg_match_all('/<a\s+href="([^"]+)">([^<]+)<\/a>\s+([\d\-a-zA-Z\s:]+)/i', $html, $matches, PREG_SET_ORDER);
    
    $bulletins = [];
    foreach ($matches as $m) {
        $href = $m[1];
        $name = trim($m[2]);
        $dateStrRaw = trim($m[3]);
        
        if (strpos($name, 'TCB#') !== 0) continue;
        
        if (!preg_match('/(\d{2}-[a-zA-Z]{3}-\d{4}\s+\d{2}:\d{2})/', $dateStrRaw, $dm)) {
            continue;
        }
        
        $dateStr = $dm[1];
        $time = strtotime($dateStr);
        if (!$time) continue;
        
        $cycloneName = 'Unknown';
        $bulletinNumber = 0;
        
        if (preg_match('/TCB#(\d+)_([a-zA-Z]+)\.pdf/i', $name, $parts)) {
            $bulletinNumber = (int)$parts[1];
            $cycloneName = ucfirst(strtolower($parts[2]));
        } else {
            continue;
        }
        
        $bulletins[] = [
            'name' => $name,
            'href' => $url . $href,
            'time' => $time,
            'date_str' => date('D, d M Y h:i A', $time),
            'bulletin_number' => $bulletinNumber,
            'cyclone_name' => $cycloneName
        ];
    }
    
    if (empty($bulletins)) return null;
    
    usort($bulletins, function($a, $b) {
        return $b['time'] - $a['time'];
    });
    
    $latest = $bulletins[0];
    
    return [
        'title' => "TROPICAL CYCLONE BULLETIN NR. " . $latest['bulletin_number'] . " (" . $latest['cyclone_name'] . ")",
        'cyclone_name' => $latest['cyclone_name'],
        'bulletin_number' => $latest['bulletin_number'],
        'description' => "Tropical Cyclone Bulletin Nr. " . $latest['bulletin_number'] . " for " . $latest['cyclone_name'] . " has been officially issued by PAGASA. Please view the official PDF to review the center coordinates, wind speed, forecast track, and tropical cyclone wind signals (TCWS) in effect.",
        'issued_at' => $latest['date_str'],
        'link' => $latest['href'],
        'quezon_city_impact' => [
            'level' => 'Prioritized',
            'severity' => 'High',
            'summary' => "LGU Alert level: Prioritized (High). Active tropical cyclone " . $latest['cyclone_name'] . " is currently monitored within or near the PAR. Quezon City LGU should prepare response assets and check vulnerable areas.",
            'action_steps' => [
                "Monitor local rain gauges and flood sensors in Quezon City.",
                "Ensure emergency shelters and evacuation teams are ready.",
                "Publish safety guidelines for citizens on community channels."
            ]
        ]
    ];
}

$rawXml = fetchRawPagasaFeed();
$isMock = false;
$bulletins = [];

if ($rawXml && strpos($rawXml, '<rss') !== false) {
    try {
        $oldEntityLoader = libxml_disable_entity_loader(true);
        $xml = simplexml_load_string($rawXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        libxml_disable_entity_loader($oldEntityLoader);

        if ($xml !== false && isset($xml->channel->item)) {
            $channel = $xml->channel;
            foreach ($channel->item as $item) {
                $title = trim((string)$item->title);
                $description = trim((string)$item->description);
                $pubDate = trim((string)$item->pubDate);
                $link = trim((string)$item->link);

                $severity = 'Medium';
                $level = 'Priority';

                $descLower = strtolower($description);
                if (strpos($descLower, 'signal no. 3') !== false || strpos($descLower, 'signal no. 4') !== false || strpos($descLower, 'signal no. 5') !== false || strpos($descLower, 'torrential') !== false) {
                    $severity = 'Critical';
                    $level = 'Urgent';
                } elseif (strpos($descLower, 'signal no. 1') !== false || strpos($descLower, 'signal no. 2') !== false || strpos($descLower, 'heavy') !== false || strpos($descLower, 'intense') !== false) {
                    $severity = 'High';
                    $level = 'Prioritized';
                }

                $cycloneName = 'None';
                if (preg_match('/"([^"]+)"/', $title, $matches)) {
                    $cycloneName = $matches[1];
                }

                $bulletinNumber = null;
                if (preg_match('/bulletin\s+nr\.\s*(\d+)/i', $title, $matches)) {
                    $bulletinNumber = (int)$matches[1];
                }

                $bulletins[] = [
                    'title' => $title,
                    'cyclone_name' => $cycloneName,
                    'bulletin_number' => $bulletinNumber,
                    'description' => $description,
                    'issued_at' => $pubDate,
                    'link' => $link,
                    'quezon_city_impact' => [
                        'level' => $level,
                        'severity' => $severity,
                        'summary' => "LGU Alert level: {$level} ({$severity}). " . (strpos($descLower, 'metro manila') !== false || strpos($descLower, 'quezon city') !== false || strpos($descLower, 'qc') !== false ? "Quezon City is explicitly affected by this advisory." : "Monitor southwest monsoon enhancement effects."),
                        'action_steps' => [
                            "Monitor local weather announcements and rainfall advisories.",
                            "Prepare disaster response equipment and alert response teams.",
                            "Clear local drainage channels to prevent waterlogging."
                        ]
                    ]
                ];
            }
        }
    } catch (Exception $e) {}
}

// Fallback to directory scraper if no bulletins found via XML
if (empty($bulletins)) {
    $latestFromDir = fetchLatestFromDirectoryIndex();
    if ($latestFromDir) {
        $bulletins[] = $latestFromDir;
    } else {
        // Fallback to mock data if scraper fails
        $rawXml = getMockPagasaFeedXml();
        $isMock = true;
        
        try {
            $oldEntityLoader = libxml_disable_entity_loader(true);
            $xml = simplexml_load_string($rawXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            libxml_disable_entity_loader($oldEntityLoader);

            if ($xml !== false && isset($xml->channel->item)) {
                $channel = $xml->channel;
                foreach ($channel->item as $item) {
                    $title = trim((string)$item->title);
                    $description = trim((string)$item->description);
                    $pubDate = trim((string)$item->pubDate);
                    $link = trim((string)$item->link);

                    $severity = 'Medium';
                    $level = 'Priority';

                    $descLower = strtolower($description);
                    if (strpos($descLower, 'signal no. 3') !== false || strpos($descLower, 'signal no. 4') !== false || strpos($descLower, 'signal no. 5') !== false || strpos($descLower, 'torrential') !== false) {
                        $severity = 'Critical';
                        $level = 'Urgent';
                    } elseif (strpos($descLower, 'signal no. 1') !== false || strpos($descLower, 'signal no. 2') !== false || strpos($descLower, 'heavy') !== false || strpos($descLower, 'intense') !== false) {
                        $severity = 'High';
                        $level = 'Prioritized';
                    }

                    $cycloneName = 'None';
                    if (preg_match('/"([^"]+)"/', $title, $matches)) {
                        $cycloneName = $matches[1];
                    }

                    $bulletinNumber = null;
                    if (preg_match('/bulletin\s+nr\.\s*(\d+)/i', $title, $matches)) {
                        $bulletinNumber = (int)$matches[1];
                    }

                    $bulletins[] = [
                        'title' => $title,
                        'cyclone_name' => $cycloneName,
                        'bulletin_number' => $bulletinNumber,
                        'description' => $description,
                        'issued_at' => $pubDate,
                        'link' => $link,
                        'quezon_city_impact' => [
                            'level' => $level,
                            'severity' => $severity,
                            'summary' => "LGU Alert level: {$level} ({$severity}). " . (strpos($descLower, 'metro manila') !== false || strpos($descLower, 'quezon city') !== false || strpos($descLower, 'qc') !== false ? "Quezon City is explicitly affected by this advisory." : "Monitor southwest monsoon enhancement effects."),
                            'action_steps' => [
                                "Monitor local weather announcements and rainfall advisories.",
                                "Prepare disaster response equipment and alert response teams.",
                                "Clear local drainage channels to prevent waterlogging."
                            ]
                        ]
                    ];
                }
            }
        } catch (Exception $e) {}
    }
}

echo json_encode([
    'success' => true,
    'is_mock' => $isMock,
    'bulletins' => $bulletins
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit();
