<?php
/**
 * Get Translations API - AI-Powered Version
 * Uses AI (OpenAI/Gemini/Claude/Groq) for high-quality translations
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');

require_once '../../ADMIN/api/db_connect.php';
require_once 'ai-translation-config.php';

session_start();

$languageCode = $_GET['lang'] ?? 'en';

// Base English translations
$baseTranslations = [
    'home.title' => 'QUEZON CITY EMERGENCY COMMUNICATION PORTAL',
    'home.mission' => 'Mission:',
    'home.mission.text' => 'To operationalize an effective, efficient, and inclusive DRRM system dedicated to Resilience-building in Quezon City communities.',
    'home.vision' => 'Vision:',
    'home.vision.text' => 'A global mode of excellence in Disaster Risk Reduction and Management for its cohesive DRRM system fostering a Sustainable, Future-ready, and Resilient Quezon City.',
    'home.emergency.call' => 'Call for Emergency',
    'home.download.title' => 'Download Our Mobile App',
    'home.download.desc' => 'Get instant emergency alerts and notifications on your mobile device',
    'home.download.download' => 'Download APK',
    'home.download.apk.desc' => 'Get the Android app now',
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
    
    // Emergency Hotlines Section
    'home.hotlines.title' => 'Quezon City Emergency Hotlines',
    'home.hotlines.desc' => 'Save these numbers for quick access during emergencies',
    'home.hotlines.helpline' => 'QC HELPLINE',
    'home.hotlines.dial' => 'DIAL 122',
    'home.hotlines.call122' => 'Call 122',
    'home.hotlines.eoc' => 'Emergency Operations Center (EOC)',
    'home.hotlines.callGlobe' => 'Call Globe',
    'home.hotlines.callSmart' => 'Call Smart',
    'home.hotlines.ems' => 'Emergency Medical Services / Urban Search and Rescue',
    'home.hotlines.callEMS' => 'Call EMS',
    'home.hotlines.landline' => 'QCDRRMO Landline',
    
    // Guest Section
    'home.guest.button' => 'Continue as Guest (Emergency Only)',
    'home.guest.notice' => 'Guest access is limited to emergency calls only.',
    'home.guest.login' => 'Login',
    'home.guest.or' => 'or',
    'home.guest.signup' => 'Sign Up',
    'home.guest.fullAccess' => 'for full access.',
];

// Filipino translations (pre-translated for speed)
$filipinoTranslations = [
    'home.title' => 'QUEZON CITY EMERGENCY COMMUNICATION PORTAL',
    'home.mission' => 'Misyon:',
    'home.mission.text' => 'Upang mapaandar ang isang epektibo, mahusay, at inclusive na DRRM system na nakatuon sa pagbuo ng Resilience sa mga komunidad ng Quezon City.',
    'home.vision' => 'Bisyon:',
    'home.vision.text' => 'Isang pandaigdigang modelo ng kahusayan sa Disaster Risk Reduction and Management para sa magkakaugnay na DRRM system na nagtataguyod ng Sustainable, Future-ready, at Resilient na Quezon City.',
    'home.emergency.call' => 'Tumawag para sa Emergency',
    'home.download.title' => 'I-download ang Aming Mobile App',
    'home.download.desc' => 'Kumuha ng instant emergency alerts at notifications sa inyong mobile device',
    'home.download.download' => 'I-download ang APK',
    'home.download.apk.desc' => 'Kunin ang Android app ngayon',
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
    
    // Emergency Hotlines Section
    'home.hotlines.title' => 'Mga Emergency Hotline ng Quezon City',
    'home.hotlines.desc' => 'I-save ang mga numerong ito para mabilis na ma-access sa oras ng emergency',
    'home.hotlines.helpline' => 'QC HELPLINE',
    'home.hotlines.dial' => 'I-DIAL ANG 122',
    'home.hotlines.call122' => 'Tumawag sa 122',
    'home.hotlines.eoc' => 'Emergency Operations Center (EOC)',
    'home.hotlines.callGlobe' => 'Tumawag sa Globe',
    'home.hotlines.callSmart' => 'Tumawag sa Smart',
    'home.hotlines.ems' => 'Emergency Medical Services / Urban Search and Rescue',
    'home.hotlines.callEMS' => 'Tumawag sa EMS',
    'home.hotlines.landline' => 'QCDRRMO Landline',
    
    // Guest Section
    'home.guest.button' => 'Magpatuloy bilang Bisita (Emergency Lamang)',
    'home.guest.notice' => 'Ang guest access ay limitado sa emergency calls lamang.',
    'home.guest.login' => 'Mag-login',
    'home.guest.or' => 'o',
    'home.guest.signup' => 'Mag-sign Up',
    'home.guest.fullAccess' => 'para sa buong access.',
];

try {
    // Check if language exists in database
    $language = null;
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM supported_languages WHERE language_code = ? AND is_active = 1");
        $stmt->execute([$languageCode]);
        $language = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$language) {
            echo json_encode([
                'success' => false,
                'message' => 'Language not supported or not active',
                'language_code' => $languageCode
            ]);
            exit;
        }
    }
    
    // Check user's auto-translate preference
    $autoTranslateEnabled = true; // Default enabled
    $userId = $_SESSION['user_id'] ?? null;
    
    if ($userId && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT auto_translate_enabled FROM user_preferences WHERE user_id = ?");
            $stmt->execute([$userId]);
            $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($prefs && isset($prefs['auto_translate_enabled'])) {
                $autoTranslateEnabled = (bool)$prefs['auto_translate_enabled'];
            }
        } catch (PDOException $e) {
            // Column might not exist yet, default to enabled
            error_log("Auto-translate preference check failed: " . $e->getMessage());
        }
    }
    
    // Select translations based on language
    $translations = [];
    $autoTranslated = false;
    
    if ($languageCode === 'en') {
        // English - use base translations
        $translations = $baseTranslations;
    } elseif ($languageCode === 'fil' || $languageCode === 'tl') {
        // Filipino - use pre-translated
        $translations = $filipinoTranslations;
    } else {
        // Other languages - Check if AI translation is enabled
        if (!$autoTranslateEnabled) {
            // User disabled auto-translation, return English
            echo json_encode([
                'success' => true,
                'language_code' => $languageCode,
                'language_name' => $language['language_name'] ?? ucfirst($languageCode),
                'native_name' => $language['native_name'] ?? '',
                'translations' => $baseTranslations,
                'auto_translated' => false,
                'ai_provider' => null,
                'note' => 'Auto-translation disabled by user. Showing English content.',
                'user_preference' => 'auto_translate_disabled'
            ]);
            exit;
        }
        
        // AI translate (user has it enabled)
        $autoTranslated = true;
        
        // First, check which translations are already cached
        $uncachedKeys = [];
        $uncachedTexts = [];
        
        foreach ($baseTranslations as $key => $englishText) {
            $cacheKey = md5($englishText . 'en' . $languageCode);
            $cached = null;
            
            if ($pdo) {
                $stmt = $pdo->prepare("
                    SELECT translated_text 
                    FROM translation_cache 
                    WHERE cache_key = ? 
                    AND TIMESTAMPDIFF(DAY, created_at, NOW()) < ?
                ");
                $stmt->execute([$cacheKey, TRANSLATION_CACHE_DAYS]);
                $cached = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            if ($cached) {
                // Use cached translation
                $translations[$key] = $cached['translated_text'];
            } else {
                // Need to translate this one
                $uncachedKeys[] = $key;
                $uncachedTexts[$key] = $englishText;
            }
        }
        
        // If there are uncached translations, do BATCH translation
        if (!empty($uncachedTexts)) {
            $translationMethod = 'ai_batch';
            
            // Try AI batch translation first
            $batchTranslations = translateBatchWithAI($uncachedTexts, 'en', $languageCode);
            
            // Check if AI translation worked (translations should be different from originals)
            $aiWorked = false;
            foreach ($batchTranslations as $key => $translated) {
                if ($translated !== $uncachedTexts[$key]) {
                    $aiWorked = true;
                    break;
                }
            }
            
            // If AI failed, fall back to MyMemory (free, no API key needed)
            if (!$aiWorked) {
                error_log("AI translation failed, falling back to MyMemory");
                $batchTranslations = translateBatchWithMyMemory($uncachedTexts, 'en', $languageCode);
                $translationMethod = 'mymemory';
            }
            
            foreach ($batchTranslations as $key => $translatedText) {
                $translations[$key] = $translatedText;
                
                // Cache the result
                $englishText = $uncachedTexts[$key];
                $cacheKey = md5($englishText . 'en' . $languageCode);
                
                if ($pdo && $translatedText !== $englishText) {
                    $stmt = $pdo->prepare("
                        INSERT INTO translation_cache 
                        (cache_key, source_text, source_lang, target_lang, translated_text, translation_method)
                        VALUES (?, ?, 'en', ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        translated_text = VALUES(translated_text),
                        updated_at = NOW()
                    ");
                    $stmt->execute([$cacheKey, $englishText, $languageCode, $translatedText, $translationMethod]);
                }
            }
        }
    }
    
    // Return translations
    echo json_encode([
        'success' => true,
        'language_code' => $languageCode,
        'language_name' => $language['language_name'] ?? 'Unknown',
        'native_name' => $language['native_name'] ?? '',
        'translations' => $translations,
        'auto_translated' => $autoTranslated,
        'ai_provider' => $autoTranslated ? AI_PROVIDER : null,
        'note' => $autoTranslated ? 'Automatically translated using ' . strtoupper(AI_PROVIDER) . ' AI' : null
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Translation error: ' . $e->getMessage(),
        'language_code' => $languageCode,
        'translations' => $baseTranslations
    ]);
}
?>
