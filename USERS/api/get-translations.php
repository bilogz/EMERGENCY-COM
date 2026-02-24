<?php
/**
 * Get Translations API - AI-Powered Version
 * Uses AI (OpenAI/Gemini/Claude/Groq) for high-quality translations
 */

// Prevent any output before headers
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error occurred',
            'error' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
        exit();
    }
});

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');

try {
    require_once '../../ADMIN/api/db_connect.php';
    require_once 'ai-translation-config.php';
    require_once 'translation-cache-store.php';
    
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load required files',
        'error' => $e->getMessage()
    ]);
    exit();
}

function resolveTranslationsLanguagesTable(PDO $pdo): ?string {
    $candidates = ['supported_languages', 'supported_languages_catalog', 'emergency_comm_supported_languages'];
    foreach ($candidates as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
            if (!$stmt || !$stmt->fetch()) {
                continue;
            }
            $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
            return $table;
        } catch (PDOException $e) {
            // Try next table.
        }
    }
    return null;
}

function normalizeTranslationsLanguage(string $lang): string {
    $lang = strtolower(trim($lang));
    if ($lang === 'tl') {
        $lang = 'fil';
    }
    if ($lang !== '' && preg_match('/^[a-z0-9_-]{2,15}$/', $lang) !== 1) {
        return '';
    }
    return $lang;
}

function detectTranslationsBrowserLanguage(): string {
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if ($acceptLanguage === '') {
        return 'en';
    }
    $first = trim(explode(',', $acceptLanguage)[0] ?? '');
    $base = strtolower(trim(explode('-', $first)[0] ?? ''));
    $normalized = normalizeTranslationsLanguage($base);
    return $normalized !== '' ? $normalized : 'en';
}

$requestedLang = normalizeTranslationsLanguage((string)($_GET['lang'] ?? ''));
$languageCode = $requestedLang;
if ($languageCode === '') {
    $sessionUserId = (int)($_SESSION['user_id'] ?? 0);
    if ($sessionUserId > 0 && isset($pdo) && $pdo instanceof PDO) {
        try {
            $prefStmt = $pdo->prepare("SELECT preferred_language FROM user_preferences WHERE user_id = ? LIMIT 1");
            $prefStmt->execute([$sessionUserId]);
            $pref = $prefStmt->fetch(PDO::FETCH_ASSOC);
            if ($pref && !empty($pref['preferred_language'])) {
                $languageCode = normalizeTranslationsLanguage((string)$pref['preferred_language']);
            }
        } catch (Throwable $e) {
            // Ignore and continue to device language fallback.
        }
    }
}
if ($languageCode === '') {
    $languageCode = detectTranslationsBrowserLanguage();
}
if ($languageCode === '') {
    $languageCode = 'en';
}

// Base English translations
$baseTranslations = [
    // Navigation
    'nav.home' => 'Home',
    'nav.alerts' => 'Alerts',
    'nav.profile' => 'Profile',
    'nav.support' => 'Support',
    'nav.weatherMap' => 'Weather Map',
    'nav.earthquakeMonitoring' => 'Earthquake Monitoring',
    'nav.emergency' => 'Emergency Call',
    'nav.login' => 'Login / Sign Up',
    'nav.user' => 'User',
    'nav.editInfo' => 'Edit Information',
    'nav.logOut' => 'Log Out',
    
    // Sidebar
    'sidebar.user' => 'User',
    'sidebar.emergency' => 'Emergency',
    'sidebar.editInfo' => 'Edit Information',
    'sidebar.logOut' => 'Log Out',
    
    // Chat
    'chat.title' => 'Quick Assistance',
    'chat.hint' => 'Please provide your information to start chatting',
    'chat.fullName' => 'Full Name',
    'chat.contactNumber' => 'Contact Number',
    'chat.location' => 'Location',
    'chat.concern' => 'What is your concern?',
    'chat.selectConcern' => 'Select a concern...',
    'chat.emergency' => 'Emergency',
    'chat.medical' => 'Medical Assistance',
    'chat.fire' => 'Fire Emergency',
    'chat.police' => 'Police Assistance',
    'chat.disaster' => 'Disaster/Weather',
    'chat.general' => 'General Inquiry',
    'chat.complaint' => 'Complaint',
    'chat.other' => 'Other',
    'chat.startChat' => 'Start Chat',
    'chat.typeMessage' => 'Type your message...',
    'chat.send' => 'Send',
    'chat.close' => 'Close chat',
    
    // Login additional
    'login.login' => 'Login',
    'login.createAccount' => 'Create Account',
    'login.or' => 'OR',
    'login.withGoogle' => 'Login with Google',
    'login.withPhone' => 'Login with Phone Number (OTP)',
    'login.verifyEmail' => 'Verify Your Email',
    'login.verifyPhone' => 'Verify Your Phone',
    'login.verificationCode' => 'Verification Code',
    'login.enterCode' => 'Enter 6-digit code',
    'login.codeHint' => 'Enter the 6-digit code sent to your email',
    'login.codeHintPhone' => 'Enter the 6-digit code sent to your phone',
    'login.verifyLogin' => 'Verify & Login',
    'login.resendCode' => 'Resend Code',
    'login.back' => 'Back',
    'login.sendOtpEmail' => 'Send OTP to Email',
    'login.emailHint' => 'We\'ll send you a verification code via email',
    
    'home.title' => 'QUEZON CITY EMERGENCY COMMUNICATION PORTAL',
    'home.header.title' => 'Welcome to Alertara',
    'home.header.subtitle' => 'Stay informed with live emergency updates in Quezon City.',
    'home.header.weatherLabel' => 'Quezon City',
    'home.header.weatherLoading' => 'Loading weather...',
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
    
    // Profile Page
    'profile.title' => 'Profile & Preferences',
    'profile.subtitle' => 'Manage your contact methods, preferred languages, and alert categories.',
    'profile.settings.title' => 'Your Settings',
    'profile.contact.title' => 'Contact Channels',
    'profile.contact.desc' => 'Update phone, email, and notification channels.',
    'profile.contact.btn' => 'Manage Channels',
    'profile.alerts.title' => 'Alert Preferences',
    'profile.alerts.desc' => 'Choose categories: Weather, Earthquake, Bomb Threat, Health, and more.',
    'profile.alerts.btn' => 'Edit Preferences',
    'profile.language.title' => 'Language Settings',
    'profile.language.desc' => 'Choose your preferred language. This will be used for alerts and interface text where available.',
    'profile.language.label' => 'Preferred Language',
    'profile.language.save' => 'Save Language Settings',
    
    // Alerts Page
    'alerts.title' => 'Live & Recent Alerts',
    'alerts.subtitle' => 'View and respond to critical alerts with clear categories and actions.',
    'alerts.active.title' => 'Active Alerts',
    'alerts.weather.title' => 'Weather Advisory',
    'alerts.weather.desc' => 'Rainfall alert from PAGASA. Stay indoors if possible.',
    'alerts.acknowledge' => 'Acknowledge',
    'alerts.earthquake.title' => 'Earthquake Update',
    'alerts.earthquake.desc' => 'Aftershock notice from PHIVOLCS. Expect minor tremors.',
    'alerts.viewDetails' => 'View Details',
    
    // Support Page
    'support.title' => 'Support & Resources',
    'support.subtitle' => 'Get guidance on responding to alerts and requesting assistance.',
    'support.help.title' => 'Help & Guidance',
    'support.respond.title' => 'How to Respond',
    'support.respond.desc' => 'Step-by-step instructions for common alert types.',
    'support.respond.btn' => 'View Guide',
    'support.dispatch.title' => 'Contact Dispatch',
    'support.dispatch.desc' => 'Reach emergency dispatch or your local incident commander.',
    'support.dispatch.btn' => 'Contact Now',
    'support.audit.title' => 'Audit & History',
    'support.audit.desc' => 'See what was sent and when for transparency.',
    'support.audit.btn' => 'Open Log',
    
    // Emergency Call Page
    'emergency.title' => 'Call for Emergency',
    'emergency.subtitle' => 'Choose the best way to reach responders—via SIM (voice/SMS) or over Internet/WiFi (VoIP/chat).',
    'emergency.callSim' => 'Call via SIM (911)',
    'emergency.callInternet' => 'Call via Internet/WiFi',
    'emergency.sim.title' => 'Call Using SIM (Voice/SMS)',
    'emergency.sim.desc' => 'Use your mobile network for the fastest connection to responders.',
    'emergency.voice.title' => 'Voice Call (SIM)',
    'emergency.voice.desc' => 'Dial national emergency 911 or your LGU hotline.',
    'emergency.call911' => 'Call 911',
    'emergency.callLGU' => 'Call LGU Hotline',
    'emergency.sms.title' => 'SMS (SIM)',
    'emergency.sms.desc' => 'Text key details (location, incident type, injuries). Keep messages short and clear.',
    'emergency.textLGU' => 'Text LGU',
    'emergency.text911' => 'Text 911',
    'emergency.internet.title' => 'Call Using Internet/WiFi',
    'emergency.internet.desc' => 'Use data or WiFi when cellular signal is weak.',
    'emergency.voip.title' => 'Web/VoIP Call',
    'emergency.voip.desc' => 'Start a voice call over WiFi.',
    'emergency.startCall' => 'Start Internet Call',
    'emergency.chat.title' => 'Two-Way Chat',
    'emergency.chat.desc' => 'Send incident details and get dispatcher replies over data.',
    'emergency.openChat' => 'Open Chat',
    'emergency.hotlines.title' => 'Quezon City Emergency Hotlines',
    'emergency.hotlines.desc' => 'Official QCDRRMO and Quezon City emergency numbers. Save these for quick access.',
    
    // Login Page
    'login.title' => 'User Login',
    'login.instruction' => 'Log in using your registered contact number and full name.',
    'login.fullName' => 'Full Name',
    'login.mobileNumber' => 'Mobile Number',
    'login.mobileHint' => 'Enter your 10-digit mobile number (without spaces)',
    'login.smsHint' => 'We\'ll send you a verification code via SMS',
    'login.sendOTP' => 'Send OTP',
    'login.backToLogin' => 'Back to Regular Login',
    
    // Signup Page
    'signup.title' => 'Create an Account',
    'signup.subtitle' => 'Sign up to receive alerts, manage your preferences, and access emergency tools.',
    'signup.fullName' => 'Full Name',
    'signup.email' => 'Email Address',
    'signup.nationality' => 'Nationality',
    'signup.mobileNumber' => 'Mobile Number',
    'signup.barangay' => 'Barangay (Quezon City)',
];

// Filipino translations (pre-translated for speed)
$filipinoTranslations = [
    'home.title' => 'QUEZON CITY EMERGENCY COMMUNICATION PORTAL',
    'home.header.title' => 'Maligayang pagdating sa Alertara',
    'home.header.subtitle' => 'Manatiling updated sa mga emergency sa Lungsod Quezon.',
    'home.header.weatherLabel' => 'Lungsod Quezon',
    'home.header.weatherLoading' => 'Nilo-load ang lagay ng panahon...',
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
    
    // Profile Page
    'profile.title' => 'Profile at Mga Kagustuhan',
    'profile.subtitle' => 'Pamahalaan ang iyong mga paraan ng pakikipag-ugnayan, mga gustong wika, at mga kategorya ng alerto.',
    'profile.settings.title' => 'Iyong Mga Setting',
    'profile.contact.title' => 'Mga Channel ng Pakikipag-ugnayan',
    'profile.contact.desc' => 'I-update ang telepono, email, at mga notification channel.',
    'profile.contact.btn' => 'Pamahalaan ang mga Channel',
    'profile.alerts.title' => 'Mga Kagustuhan sa Alerto',
    'profile.alerts.desc' => 'Pumili ng mga kategorya: Panahon, Lindol, Banta ng Bomba, Kalusugan, at iba pa.',
    'profile.alerts.btn' => 'I-edit ang mga Kagustuhan',
    'profile.language.title' => 'Mga Setting ng Wika',
    'profile.language.desc' => 'Piliin ang iyong gustong wika. Ito ay gagamitin para sa mga alerto at teksto ng interface kung available.',
    'profile.language.label' => 'Gustong Wika',
    'profile.language.save' => 'I-save ang mga Setting ng Wika',
    
    // Alerts Page
    'alerts.title' => 'Mga Live at Kamakailang Alerto',
    'alerts.subtitle' => 'Tingnan at tumugon sa mga kritikal na alerto na may malinaw na mga kategorya at aksyon.',
    'alerts.active.title' => 'Mga Aktibong Alerto',
    'alerts.weather.title' => 'Payo sa Panahon',
    'alerts.weather.desc' => 'Alerto sa pag-ulan mula sa PAGASA. Manatili sa loob kung maaari.',
    'alerts.acknowledge' => 'Kilalanin',
    'alerts.earthquake.title' => 'Update sa Lindol',
    'alerts.earthquake.desc' => 'Abiso ng aftershock mula sa PHIVOLCS. Asahan ang mga minor na pagyanig.',
    'alerts.viewDetails' => 'Tingnan ang mga Detalye',
    
    // Support Page
    'support.title' => 'Suporta at mga Mapagkukunan',
    'support.subtitle' => 'Kumuha ng gabay sa pagtugon sa mga alerto at paghiling ng tulong.',
    'support.help.title' => 'Tulong at Gabay',
    'support.respond.title' => 'Paano Tumugon',
    'support.respond.desc' => 'Mga hakbang-hakbang na tagubilin para sa mga karaniwang uri ng alerto.',
    'support.respond.btn' => 'Tingnan ang Gabay',
    'support.dispatch.title' => 'Makipag-ugnayan sa Dispatch',
    'support.dispatch.desc' => 'Makipag-ugnayan sa emergency dispatch o sa iyong lokal na incident commander.',
    'support.dispatch.btn' => 'Makipag-ugnayan Ngayon',
    'support.audit.title' => 'Audit at Kasaysayan',
    'support.audit.desc' => 'Tingnan kung ano ang ipinadala at kailan para sa transparency.',
    'support.audit.btn' => 'Buksan ang Log',
    
    // Emergency Call Page
    'emergency.title' => 'Tumawag para sa Emergency',
    'emergency.subtitle' => 'Piliin ang pinakamahusay na paraan upang makipag-ugnayan sa mga responder—sa pamamagitan ng SIM (boses/SMS) o sa Internet/WiFi (VoIP/chat).',
    'emergency.callSim' => 'Tumawag sa SIM (911)',
    'emergency.callInternet' => 'Tumawag sa Internet/WiFi',
    'emergency.sim.title' => 'Tumawag Gamit ang SIM (Boses/SMS)',
    'emergency.sim.desc' => 'Gamitin ang iyong mobile network para sa pinakamabilis na koneksyon sa mga responder.',
    'emergency.voice.title' => 'Tawag sa Boses (SIM)',
    'emergency.voice.desc' => 'I-dial ang pambansang emergency 911 o ang hotline ng iyong LGU.',
    'emergency.call911' => 'Tumawag sa 911',
    'emergency.callLGU' => 'Tumawag sa LGU Hotline',
    'emergency.sms.title' => 'SMS (SIM)',
    'emergency.sms.desc' => 'Mag-text ng mahahalagang detalye (lokasyon, uri ng insidente, mga pinsala). Panatilihing maikli at malinaw ang mga mensahe.',
    'emergency.textLGU' => 'Mag-text sa LGU',
    'emergency.text911' => 'Mag-text sa 911',
    'emergency.internet.title' => 'Tumawag Gamit ang Internet/WiFi',
    'emergency.internet.desc' => 'Gumamit ng data o WiFi kapag mahina ang cellular signal.',
    'emergency.voip.title' => 'Web/VoIP na Tawag',
    'emergency.voip.desc' => 'Magsimula ng voice call sa WiFi.',
    'emergency.startCall' => 'Simulan ang Internet Call',
    'emergency.chat.title' => 'Two-Way Chat',
    'emergency.chat.desc' => 'Magpadala ng mga detalye ng insidente at makatanggap ng mga tugon ng dispatcher sa data.',
    'emergency.openChat' => 'Buksan ang Chat',
    'emergency.hotlines.title' => 'Mga Emergency Hotline ng Quezon City',
    'emergency.hotlines.desc' => 'Opisyal na mga numero ng QCDRRMO at Quezon City. I-save ang mga ito para sa mabilis na access.',
    
    // Login Page
    'login.title' => 'User Login',
    'login.instruction' => 'Log in using your registered contact number and full name.',
    'login.fullName' => 'Full Name',
    'login.mobileNumber' => 'Mobile Number',
    'login.mobileHint' => 'Enter your 10-digit mobile number (without spaces)',
    'login.smsHint' => 'We\'ll send you a verification code via SMS',
    'login.sendOTP' => 'Send OTP',
    'login.backToLogin' => 'Back to Regular Login',
    'login.login' => 'Login',
    'login.createAccount' => 'Create Account',
    'login.or' => 'OR',
    'login.withGoogle' => 'Login with Google',
    'login.withPhone' => 'Login with Phone Number (OTP)',
    'login.verifyEmail' => 'Verify Your Email',
    'login.verifyPhone' => 'Verify Your Phone',
    'login.verificationCode' => 'Verification Code',
    'login.enterCode' => 'Enter 6-digit code',
    'login.codeHint' => 'Enter the 6-digit code sent to your email',
    'login.codeHintPhone' => 'Enter the 6-digit code sent to your phone',
    'login.verifyLogin' => 'Verify & Login',
    'login.resendCode' => 'Resend Code',
    'login.back' => 'Back',
    'login.sendOtpEmail' => 'Send OTP to Email',
    'login.emailHint' => 'We\'ll send you a verification code via email',
    
    // Signup Page
    'signup.title' => 'Gumawa ng Account',
    'signup.subtitle' => 'Mag-sign up upang makatanggap ng mga alerto, pamahalaan ang iyong mga kagustuhan, at ma-access ang mga emergency tool.',
    'signup.fullName' => 'Buong Pangalan',
    'signup.email' => 'Email Address',
    'signup.nationality' => 'Nasyonalidad',
    'signup.mobileNumber' => 'Numero ng Mobile',
    'signup.barangay' => 'Barangay (Quezon City)',
    
    // Sidebar
    'sidebar.user' => 'User',
    'sidebar.emergency' => 'Emergency',
    'sidebar.editInfo' => 'I-edit ang Impormasyon',
    'sidebar.logOut' => 'Mag-logout',
    
    // Chat
    'chat.title' => 'Mabilis na Tulong',
    'chat.hint' => 'Mangyaring magbigay ng iyong impormasyon upang magsimulang mag-chat',
    'chat.fullName' => 'Buong Pangalan',
    'chat.contactNumber' => 'Numero ng Kontakto',
    'chat.location' => 'Lokasyon',
    'chat.concern' => 'Ano ang iyong alalahanin?',
    'chat.selectConcern' => 'Pumili ng alalahanin...',
    'chat.emergency' => 'Emergency',
    'chat.medical' => 'Tulong Medikal',
    'chat.fire' => 'Emergency sa Sunog',
    'chat.police' => 'Tulong ng Pulis',
    'chat.disaster' => 'Kalamidad/Panahon',
    'chat.general' => 'Pangkalahatang Tanong',
    'chat.complaint' => 'Reklamo',
    'chat.other' => 'Iba pa',
    'chat.startChat' => 'Simulan ang Chat',
    'chat.typeMessage' => 'I-type ang iyong mensahe...',
    'chat.send' => 'Ipadala',
    'chat.close' => 'Isara ang chat',
    
    // Login additional
    'login.login' => 'Mag-login',
    'login.createAccount' => 'Gumawa ng Account',
    'login.or' => 'O',
    'login.withGoogle' => 'Mag-login gamit ang Google',
    'login.withPhone' => 'Mag-login gamit ang Numero ng Telepono (OTP)',
    'login.verifyEmail' => 'I-verify ang Iyong Email',
    'login.verifyPhone' => 'I-verify ang Iyong Telepono',
    'login.verificationCode' => 'Verification Code',
    'login.enterCode' => 'Ilagay ang 6-digit code',
    'login.codeHint' => 'Ilagay ang 6-digit code na ipinadala sa iyong email',
    'login.codeHintPhone' => 'Ilagay ang 6-digit code na ipinadala sa iyong telepono',
    'login.verifyLogin' => 'I-verify at Mag-login',
    'login.resendCode' => 'Ipadala ulit ang Code',
    'login.back' => 'Bumalik',
    'login.sendOtpEmail' => 'Ipadala ang OTP sa Email',
    'login.emailHint' => 'Magpapadala kami ng verification code sa pamamagitan ng email',
    
    // Common UI elements
    'common.save' => 'Save',
    'common.cancel' => 'Cancel',
    'common.close' => 'Close',
    'common.loading' => 'Loading...',
    'common.submit' => 'Submit',
    'common.delete' => 'Delete',
    'common.edit' => 'Edit',
    'common.update' => 'Update',
    'common.add' => 'Add',
    'common.remove' => 'Remove',
    'common.search' => 'Search',
    'common.filter' => 'Filter',
    'common.clear' => 'Clear',
    'common.apply' => 'Apply',
    'common.reset' => 'Reset',
    'common.pleaseWait' => 'Please wait...',
    'common.error' => 'Error',
    'common.success' => 'Success',
    'common.warning' => 'Warning',
    'common.info' => 'Info',
    'common.yes' => 'Yes',
    'common.no' => 'No',
    'common.ok' => 'OK',
    'common.confirm' => 'Confirm',
    'common.back' => 'Back',
    'common.next' => 'Next',
    'common.previous' => 'Previous',
    'common.continue' => 'Continue',
    'common.finish' => 'Finish',
    'common.select' => 'Select',
    'common.choose' => 'Choose',
    'common.view' => 'View',
    'common.details' => 'Details',
    'common.more' => 'More',
    'common.less' => 'Less',
    'common.show' => 'Show',
    'common.hide' => 'Hide',
    'common.download' => 'Download',
    'common.upload' => 'Upload',
    'common.send' => 'Send',
    'common.receive' => 'Receive',
    'common.refresh' => 'Refresh',
    'common.reload' => 'Reload',
    
    // Form elements
    'form.fullName' => 'Full Name',
    'form.email' => 'Email Address',
    'form.mobileNumber' => 'Mobile Number',
    'form.phoneNumber' => 'Phone Number',
    'form.address' => 'Address',
    'form.city' => 'City',
    'form.country' => 'Country',
    'form.nationality' => 'Nationality',
    'form.password' => 'Password',
    'form.confirmPassword' => 'Confirm Password',
    'form.username' => 'Username',
    'form.enterName' => 'Enter your name',
    'form.enterEmail' => 'Enter your email',
    'form.enterPhone' => 'Enter your phone',
    'form.select' => 'Select...',
    'form.choose' => 'Choose...',
    'form.required' => 'Required',
    'form.optional' => 'Optional',
];

// Clean output buffer before processing
ob_clean();

try {
    // Check if language exists in database
    $language = null;
    if ($pdo) {
        try {
            $languagesTable = resolveTranslationsLanguagesTable($pdo);
            if ($languagesTable !== null) {
                $stmt = $pdo->prepare("SELECT * FROM {$languagesTable} WHERE language_code = ? AND is_active = 1");
                $stmt->execute([$languageCode]);
                $language = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $language = ['language_code' => $languageCode, 'language_name' => ucfirst($languageCode)];
            }
        } catch (PDOException $e) {
            // Table might not exist, continue without language check
            error_log("Supported languages table check failed: " . $e->getMessage());
            $language = ['language_code' => $languageCode, 'language_name' => ucfirst($languageCode)];
        }
        
        if (!$language) {
            // If language not found, create a default entry
            $language = ['language_code' => $languageCode, 'language_name' => ucfirst($languageCode)];
        }
    } else {
        // No database connection, use default
        $language = ['language_code' => $languageCode, 'language_name' => ucfirst($languageCode)];
    }
    
    // Select translations based on language
    $translations = [];
    $autoTranslated = false;
    $translationProvider = defined('TRANSLATION_PROVIDER') ? TRANSLATION_PROVIDER : (defined('AI_PROVIDER') ? AI_PROVIDER : 'argos');

    if ($languageCode === 'en') {
        // English - use base translations
        $translations = $baseTranslations;
    } else {
        // Citizen-side translation path is ArgosTranslate-first for all non-English languages.
        $autoTranslated = true;
        
        // First, check which translations are already cached
        $uncachedTexts = [];
        
        foreach ($baseTranslations as $key => $englishText) {
            $cacheKey = md5($englishText . 'en' . $languageCode);
            $cachedText = translation_cache_read($cacheKey, TRANSLATION_CACHE_DAYS, $pdo ?? null);

            if ($cachedText !== null) {
                // Use cached translation
                $translations[$key] = $cachedText;
            } else {
                // Need to translate this one
                $uncachedTexts[$key] = $englishText;
            }
        }
        
        // If there are uncached translations, do BATCH translation
        if (!empty($uncachedTexts)) {
            $translationMethod = 'argos';
            $batchTranslations = translateBatchWithArgos($uncachedTexts, 'en', $languageCode);

            // If Argos is unavailable, keep a Filipino static fallback for user continuity.
            $translatedCount = 0;
            foreach ($batchTranslations as $key => $translatedText) {
                $original = $uncachedTexts[$key] ?? '';
                if (trim((string)$translatedText) !== '' && trim((string)$translatedText) !== trim((string)$original)) {
                    $translatedCount++;
                }
            }
            if ($translatedCount === 0 && ($languageCode === 'fil' || $languageCode === 'tl')) {
                foreach ($uncachedTexts as $key => $englishText) {
                    $batchTranslations[$key] = $filipinoTranslations[$key] ?? $englishText;
                }
                $translationMethod = 'fallback_static_fil';
            }
            
            foreach ($batchTranslations as $key => $translatedText) {
                $translations[$key] = $translatedText;
                
                // Cache the result
                $englishText = $uncachedTexts[$key];
                $cacheKey = md5($englishText . 'en' . $languageCode);
                
                if ($translatedText !== $englishText) {
                    translation_cache_write(
                        $cacheKey,
                        $englishText,
                        'en',
                        $languageCode,
                        $translatedText,
                        $translationMethod,
                        $pdo ?? null
                    );
                }
            }
        }

        // Ensure no missing keys even when translation service is partially unavailable.
        foreach ($baseTranslations as $key => $englishText) {
            if (!isset($translations[$key]) || trim((string)$translations[$key]) === '') {
                $translations[$key] = $englishText;
            }
        }
    }
    
    // Ensure clean output before JSON
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Return translations
    $response = [
        'success' => true,
        'language_code' => $languageCode,
        'language_name' => $language['language_name'] ?? ucfirst($languageCode),
        'native_name' => $language['native_name'] ?? '',
        'translations' => $translations,
        'auto_translated' => $autoTranslated,
        'translation_provider' => $autoTranslated ? $translationProvider : null,
        'note' => $autoTranslated ? 'Automatically translated using ArgosTranslate' : null
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
    if (ob_get_level()) {
        ob_end_flush();
    }
    
} catch (Exception $e) {
    error_log("Get Translations API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Ensure clean output
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Translation error occurred',
        'error' => $e->getMessage(),
        'language_code' => $languageCode ?? 'en',
        'translations' => $baseTranslations ?? []
    ]);
    
    if (ob_get_level()) {
        ob_end_flush();
    }
} catch (Error $e) {
    error_log("Get Translations API Fatal Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Ensure clean output
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error occurred',
        'error' => $e->getMessage(),
        'language_code' => $languageCode ?? 'en',
        'translations' => $baseTranslations ?? []
    ]);
    
    if (ob_get_level()) {
        ob_end_flush();
    }
}
?>
