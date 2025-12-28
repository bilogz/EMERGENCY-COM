<?php
/**
 * Translation API - Get translations for specific language
 * Returns translations from database or uses AI translation service
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');

require_once '../../ADMIN/api/db_connect.php';

$languageCode = $_GET['lang'] ?? 'en';

// Base English translations (fallback)
$baseTranslations = [
    'home.title' => 'QUEZON CITY EMERGENCY COMMUNICATION PORTAL',
    'home.mission' => 'Mission:',
    'home.mission.text' => 'To operationalize an effective, efficient, and inclusive DRRM system dedicated to Resilience-building in Quezon City communities.',
    'home.vision' => 'Vision:',
    'home.vision.text' => 'A global mode of excellence in Disaster Risk Reduction and Management for its cohesive DRRM system fostering a Sustainable, Future-ready, and Resilient Quezon City.',
    'home.emergency.call' => 'Call for Emergency',
    'home.download.title' => 'Download Our Mobile App',
    'home.download.desc' => 'Get instant emergency alerts and notifications on your mobile device',
    'home.download.comingsoon' => 'Coming Soon',
    'home.download.comingsoon.desc' => 'Mobile app launching soon',
    'home.download.badge' => 'SOON',
    'home.about.title' => 'About Us',
    'home.about.text' => 'The Quezon City Emergency Communication Portal connects residents, responders, and the local government through reliable, multi-channel emergency alerts and communication tools. Our goal is to help you receive critical information quickly and safely during disasters, incidents, and city-wide emergencies.',
    'home.services.title' => 'Services',
    'home.services.mass' => 'Mass Notifications',
    'home.services.mass.desc' => 'City-wide alerts sent via SMS, email, and online channels for urgent incidents and advisories.',
    'home.services.twoWay' => 'Two-Way Communication',
    'home.services.twoWay.desc' => 'Residents can report incidents, request assistance, and send updates back to responders.',
    'home.services.automated' => 'Automated Hazard Feeds',
    'home.services.automated.desc' => 'Integrated updates from agencies such as PAGASA and PHIVOLCS for weather and seismic events.',
    'home.services.multilingual' => 'Multilingual Alerts',
    'home.services.multilingual.desc' => 'Important messages can be delivered in multiple languages to reach more communities.',
    'home.guide.title' => 'Guide: How to Call for Emergency',
    'home.guide.1' => 'Stay calm and move to a safe place.',
    'home.guide.2' => 'Use the "Call for Emergency" button.',
    'home.guide.3' => 'Prepare key details.',
    'home.guide.4' => 'Follow instructions.',
    'home.guide.5' => 'Keep lines open.',
];

// Filipino translations
$filipinoTranslations = [
    'home.title' => 'QUEZON CITY EMERGENCY COMMUNICATION PORTAL',
    'home.mission' => 'Misyon:',
    'home.mission.text' => 'Upang mapaandar ang isang epektibo, mahusay, at inclusive na DRRM system na nakatuon sa pagbuo ng Resilience sa mga komunidad ng Quezon City.',
    'home.vision' => 'Bisyon:',
    'home.vision.text' => 'Isang pandaigdigang modelo ng kahusayan sa Disaster Risk Reduction and Management para sa magkakaugnay na DRRM system na nagtataguyod ng Sustainable, Future-ready, at Resilient na Quezon City.',
    'home.emergency.call' => 'Tumawag para sa Emergency',
    'home.download.title' => 'I-download ang Aming Mobile App',
    'home.download.desc' => 'Kumuha ng instant emergency alerts at notifications sa inyong mobile device',
    'home.download.comingsoon' => 'Darating Na',
    'home.download.comingsoon.desc' => 'Malapit nang i-launch ang mobile app',
    'home.download.badge' => 'DARATING',
    'home.about.title' => 'Tungkol sa Amin',
    'home.about.text' => 'Ang Quezon City Emergency Communication Portal ay nag-uugnay sa mga residente, responders, at lokal na pamahalaan sa pamamagitan ng maaasahang, multi-channel na emergency alerts at communication tools. Layunin naming tulungan kayong makatanggap ng mahahalagang impormasyon nang mabilis at ligtas sa panahon ng mga kalamidad, insidente, at city-wide na emergencies.',
    'home.services.title' => 'Mga Serbisyo',
    'home.services.mass' => 'Mass Notifications',
    'home.services.mass.desc' => 'City-wide na mga alert na ipinapadala sa pamamagitan ng SMS, email, at online channels para sa mga urgent na insidente at advisories.',
    'home.services.twoWay' => 'Two-Way Communication',
    'home.services.twoWay.desc' => 'Maaaring mag-ulat ang mga residente ng mga insidente, humingi ng tulong, at magpadala ng updates pabalik sa responders.',
    'home.services.automated' => 'Automated Hazard Feeds',
    'home.services.automated.desc' => 'Integrated na updates mula sa mga ahensya tulad ng PAGASA at PHIVOLCS para sa weather at seismic events.',
    'home.services.multilingual' => 'Multilingual Alerts',
    'home.services.multilingual.desc' => 'Mahahalagang mensahe ay maaaring maihatid sa maraming wika upang maabot ang mas maraming komunidad.',
    'home.guide.title' => 'Gabay: Paano Tumawag para sa Emergency',
    'home.guide.1' => 'Manatiling kalmado at lumipat sa ligtas na lugar.',
    'home.guide.2' => 'Gamitin ang "Call for Emergency" button.',
    'home.guide.3' => 'Maghanda ng mahahalagang detalye.',
    'home.guide.4' => 'Sundin ang mga tagubilin.',
    'home.guide.5' => 'Panatilihing bukas ang linya.',
];

// Select appropriate translations
$translations = $baseTranslations;

if ($languageCode === 'fil' || $languageCode === 'tl') {
    $translations = $filipinoTranslations;
} elseif ($languageCode !== 'en') {
    // For other languages, return English with a note
    // In production, you would integrate with a translation service here
    $translations = $baseTranslations;
}

// Check if language exists in database
try {
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM supported_languages WHERE language_code = ? AND is_active = 1");
        $stmt->execute([$languageCode]);
        $language = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($language) {
            echo json_encode([
                'success' => true,
                'language_code' => $languageCode,
                'language_name' => $language['language_name'],
                'native_name' => $language['native_name'],
                'translations' => $translations,
                'is_ai_supported' => (bool)$language['is_ai_supported'],
                'note' => ($languageCode !== 'en' && $languageCode !== 'fil' && $languageCode !== 'tl') 
                    ? 'Using English translations as fallback. Full translation support coming soon.' 
                    : null
            ]);
        } else {
            // Language not found or not active
            echo json_encode([
                'success' => false,
                'message' => 'Language not supported or not active',
                'language_code' => $languageCode,
                'translations' => $baseTranslations
            ]);
        }
    } else {
        // Database not available, return base translations
        echo json_encode([
            'success' => true,
            'language_code' => $languageCode,
            'translations' => $translations,
            'note' => 'Database unavailable, using static translations'
        ]);
    }
} catch (Exception $e) {
    // Error occurred, return base translations
    echo json_encode([
        'success' => true,
        'language_code' => $languageCode,
        'translations' => $translations,
        'error' => $e->getMessage()
    ]);
}
?>

