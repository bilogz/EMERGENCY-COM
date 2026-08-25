<?php
/**
 * Dialogflow API Endpoint
 * Handles AI-powered chatbot integration for notification assistance
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
if (empty($input['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required field: message']);
    exit;
}

try {
    // Get notification context if provided
    $notificationContext = isset($input['context']) ? $input['context'] : [];
    
    // Generate contextual response
    $response = generateContextualResponse($input['message'], $notificationContext);
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

function generateContextualResponse($message, $context) {
    $lowerMessage = strtolower($message);
    
    // Extract notification details from context
    $title = isset($context['notificationTitle']) ? $context['notificationTitle'] : 'this notification';
    $category = isset($context['notificationCategory']) ? $context['notificationCategory'] : 'unknown';
    $severity = isset($context['notificationSeverity']) ? $context['notificationSeverity'] : 'unknown';
    $description = isset($context['notificationDescription']) ? $context['notificationDescription'] : '';
    
    // Enhanced pattern matching for all 8 fixed questions
    $patterns = [
        'alert_type' => ['type', 'category', 'kind', 'what type', 'what kind', 'classification'],
        'action' => ['what should i do', 'action', 'steps', 'guidelines', 'respond', 'react'],
        'urgent' => ['is this urgent', 'urgent', 'immediate', 'emergency', 'critical'],
        'severity_meaning' => ['severity', 'serious', 'level', 'how serious', 'what does severity'],
        'source' => ['who sent', 'source', 'from', 'who issued', 'author', 'sender'],
        'duration' => ['how long', 'duration', 'when will it end', 'how long will it last', 'last'],
        'area' => ['area', 'affected', 'location', 'where', 'my area', 'zone'],
        'safety' => ['safety', 'safe', 'protect', 'guidelines', 'precautions', 'stay safe'],
    ];

    $intent = 'general_info';
    $replyText = '';
    
    foreach ($patterns as $key => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($lowerMessage, $keyword) !== false) {
                $intent = $key;
                break;
            }
        }
        if ($intent !== 'general_info') break;
    }

    // Generate highly informative contextual responses
    switch ($intent) {
        case 'alert_type':
            $replyText = "This is a \"{$category}\" type alert titled \"{$title}\". ";
            $replyText .= getCategoryDetailedDescription($category);
            $replyText .= " This category helps you understand the nature and expected impact of the alert.";
            break;
            
        case 'action':
            $replyText = "For this {$category} alert with {$severity} severity titled \"{$title}\": ";
            $replyText .= getCategoryDetailedAction($category, $severity);
            if (!empty($description)) {
                $replyText .= " Additional context from the notification: {$description}";
            }
            $replyText .= " Please follow these instructions carefully for your safety.";
            break;
            
        case 'urgent':
            $replyText = getUrgencyAssessment($severity, $category);
            $replyText .= " This {$category} alert with {$severity} severity ";
            $replyText .= getUrgencyAction($severity);
            break;
            
        case 'severity_meaning':
            $replyText = "The \"{$severity}\" severity level for this {$category} alert means: ";
            $replyText .= getDetailedSeverityExplanation($severity);
            $replyText .= " For this specific alert type, {$severity} severity indicates ";
            $replyText .= getCategorySeverityContext($category, $severity);
            break;
            
        case 'source':
            $replyText = "This \"{$title}\" notification was issued by official government emergency authorities. ";
            $replyText .= "For {$category} alerts, the issuing authority is typically ";
            $replyText .= getCategorySource($category);
            $replyText .= " These are authorized government agencies responsible for public safety notifications.";
            break;
            
        case 'duration':
            $replyText = "For this {$category} alert: ";
            $replyText .= getCategoryDetailedDuration($category);
            $replyText .= " Given the {$severity} severity, you should ";
            $replyText .= getDurationPreparation($severity);
            $replyText .= " Monitor official channels for updates on when this alert will be lifted.";
            break;
            
        case 'area':
            $replyText = "This \"{$title}\" alert covers specific geographical areas. ";
            if (!empty($description)) {
                $replyText .= "Based on the notification details: {$description}. ";
            }
            $replyText .= "To determine if your area is affected: ";
            $replyText .= getAreaCheckInstructions($category);
            $replyText .= " If unsure, assume you may be affected and take appropriate precautions.";
            break;
            
        case 'safety':
            $replyText = "For this {$category} alert with {$severity} severity, follow these comprehensive safety guidelines: ";
            $replyText .= getCategoryDetailedSafety($category, $severity);
            $replyText .= " These guidelines are specifically tailored for this type of alert and severity level.";
            break;
            
        default:
            $replyText = "This is a \"{$title}\" notification. It's a {$category} alert with {$severity} severity. ";
            if (!empty($description)) {
                $replyText .= "Details: {$description}. ";
            }
            $replyText .= "You can ask me specific questions: What type of alert is this? What should I do? Is this urgent? What does this severity mean? Who sent this notification? How long will this last? Is my area affected? What are the safety guidelines?";
            break;
    }

    return [
        'success' => true,
        'replyText' => $replyText,
        'intent' => $intent,
        'confidence' => 0.85,
        'shouldEscalate' => $severity === 'HIGH' || $severity === 'CRITICAL',
        'sessionId' => 'session_' . time() . '_' . bin2hex(random_bytes(4))
    ];
}

function getCategoryDetailedDescription($category) {
    $descriptions = [
        'Weather Forecast' => 'This indicates weather-related conditions requiring attention. Weather alerts include typhoon warnings, flood advisories, rainfall alerts, and temperature extremes. These alerts help you prepare for meteorological events that may affect your area.',
        'Emergency' => 'This indicates an urgent situation requiring immediate attention. Emergency alerts include fire incidents, medical emergencies, crime reports, flood situations, earthquakes, and other critical incidents that pose immediate threats to life or property.',
        'Safety' => 'This indicates general safety advisories and precautionary information. Safety alerts provide guidance on potential hazards, health advisories, environmental concerns, and other safety-related information that requires awareness.',
        'Announcement' => 'This indicates official announcements and public service information. Announcements include policy updates, schedule changes, community events, important notices, and official communications from government agencies.',
        'General' => 'This indicates informational updates and general notifications. General alerts provide routine information, updates, and notifications that may be relevant to the public but do not require immediate action.',
    ];
    return isset($descriptions[$category]) ? $descriptions[$category] : 'This is a standard notification type that requires your attention.';
}

function getCategoryDetailedAction($category, $severity) {
    $actions = [
        'Weather Forecast' => 'Monitor weather conditions regularly through official channels. Secure outdoor items and prepare emergency supplies. Avoid travel if advised by authorities. If evacuation is ordered, follow designated routes immediately. Keep important documents in waterproof containers.',
        'Emergency' => 'Assess your immediate safety and the safety of others. Call emergency services (911) if in immediate danger. Follow all emergency protocols and instructions from authorities. Evacuate if instructed and help others only if safe to do so. Stay informed through official emergency channels.',
        'Safety' => 'Review and implement all recommended precautions. Stay informed through official channels and report any safety concerns to authorities. Follow health advisories and environmental guidelines. Prepare emergency supplies and know emergency contact numbers.',
        'Announcement' => 'Carefully review the announcement details and follow any specific instructions provided. Share important information with family and community members. Stay informed about any follow-up announcements or updates. Take appropriate action based on the announcement content.',
        'General' => 'Review the information provided and follow any specific instructions mentioned. Stay informed about any updates or changes. Take appropriate action based on the notification content and your personal situation.',
    ];
    
    $baseAction = isset($actions[$category]) ? $actions[$category] : 'Follow official instructions.';
    
    if ($severity === 'HIGH' || $severity === 'CRITICAL') {
        return '⚠️ HIGH PRIORITY: ' . $baseAction . ' Given the critical severity, act immediately and do not delay.';
    } elseif ($severity === 'MEDIUM') {
        return '⚡ ATTENTION REQUIRED: ' . $baseAction . ' Monitor the situation closely and be prepared to escalate your response if conditions worsen.';
    }
    return 'ℹ️ INFORMATIONAL: ' . $baseAction . ' Stay informed but no immediate emergency action is required.';
}

function getUrgencyAssessment($severity, $category) {
    $urgency = [
        'HIGH' => '⚠️ YES - This is HIGH urgency. This alert requires immediate attention and action.',
        'MEDIUM' => '⚡ MODERATE - This is MEDIUM urgency. Monitor the situation closely and be prepared to act.',
        'LOW' => 'ℹ️ LOW - This is LOW urgency. Stay informed but no immediate action required.',
        'CRITICAL' => '🚨 CRITICAL - This is CRITICAL urgency. Take emergency action immediately!',
    ];
    
    $baseUrgency = isset($urgency[$severity]) ? $urgency[$severity] : 'Follow official instructions.';
    
    $categoryContext = [
        'Weather Forecast' => 'Weather conditions can change rapidly, so stay alert.',
        'Emergency' => 'Emergency situations can escalate quickly, so act promptly.',
        'Safety' => 'Safety situations require ongoing awareness.',
        'Announcement' => 'Announcements may require timely response.',
        'General' => 'General notifications should be reviewed promptly.',
    ];
    
    return $baseUrgency . ' ' . (isset($categoryContext[$category]) ? $categoryContext[$category] : '');
}

function getUrgencyAction($severity) {
    $actions = [
        'HIGH' => 'requires you to take immediate action without delay. Prioritize this alert and follow all instructions.',
        'MEDIUM' => 'requires you to stay alert and be prepared to take action if the situation worsens.',
        'LOW' => 'requires you to stay informed but no immediate action is necessary.',
        'CRITICAL' => 'requires emergency action immediately. This is the highest priority alert.',
    ];
    return isset($actions[$severity]) ? $actions[$severity] : 'follow official instructions.';
}

function getDetailedSeverityExplanation($severity) {
    $explanations = [
        'HIGH' => 'HIGH severity indicates a significant threat or situation that requires immediate action. There is potential for serious impact to life, property, or infrastructure. You should respond promptly and follow all official instructions.',
        'MEDIUM' => 'MEDIUM severity indicates a situation that requires attention and monitoring. While not immediately critical, conditions could worsen. You should stay informed, prepare to take action, and monitor official updates.',
        'LOW' => 'LOW severity indicates informational or routine notifications. There is no immediate threat, but the information may be relevant to you. Stay informed and review the details.',
        'CRITICAL' => 'CRITICAL severity indicates an extreme or life-threatening situation. Immediate emergency action is required to protect life and property. This is the highest alert level and demands urgent response.',
    ];
    return isset($explanations[$severity]) ? $explanations[$severity] : 'Please follow official instructions.';
}

function getCategorySeverityContext($category, $severity) {
    $contexts = [
        'Weather Forecast' => 'the weather conditions pose significant risk and you should take weather-specific precautions.',
        'Emergency' => 'the emergency situation is serious and requires immediate response following emergency protocols.',
        'Safety' => 'the safety concern is significant and you should implement all recommended safety measures.',
        'Announcement' => 'the announcement contains important information that requires your attention.',
        'General' => 'the notification contains relevant information that you should review.',
    ];
    return isset($contexts[$category]) ? $contexts[$category] : 'follow official guidelines.';
}

function getCategorySource($category) {
    $sources = [
        'Weather Forecast' => 'PAGASA (Philippine Atmospheric, Geophysical and Astronomical Services Administration) and local weather bureaus.',
        'Emergency' => 'QCDRRMO (Quezon City Disaster Risk Reduction Management Office), QCPD (Quezon City Police District), QC Fire District, and national emergency services.',
        'Safety' => 'Local health departments, environmental agencies, and safety authorities.',
        'Announcement' => 'Local government units, national government agencies, and official government channels.',
        'General' => 'Various government agencies and official sources.',
    ];
    return isset($sources[$category]) ? $sources[$category] : 'relevant government authorities.';
}

function getCategoryDetailedDuration($category) {
    $durations = [
        'Weather Forecast' => 'Weather alerts typically last 6-24 hours depending on the weather system. Typhoon warnings may last 24-48 hours or longer. Flood advisories remain in effect until water levels recede. Weather conditions are monitored continuously and alerts are updated as conditions change.',
        'Emergency' => 'Emergency alerts remain active until the situation is fully resolved and officially lifted by authorities. This can range from a few hours for minor incidents to several days for major emergencies. The alert duration depends on the nature and scale of the emergency.',
        'Safety' => 'Safety advisories may remain in effect for several days or weeks until the hazard is mitigated or the situation is resolved. Health advisories may last based on medical recommendations. Environmental alerts continue until conditions improve.',
        'Announcement' => 'Announcements are typically one-time notifications, but follow-up announcements may be issued if there are updates or additional information. Some announcements may have specific timeframes or deadlines mentioned.',
        'General' => 'Duration varies based on the specific nature of the notification. Some may be time-sensitive while others provide ongoing information. Monitor official channels for updates.',
    ];
    return isset($durations[$category]) ? $durations[$category] : 'Monitor official updates for duration information.';
}

function getDurationPreparation($severity) {
    $preparations = [
        'HIGH' => 'be prepared for extended duration and maintain readiness for an extended period.',
        'MEDIUM' => 'prepare for potential extended duration and monitor for any changes.',
        'LOW' => 'stay informed but no extended preparation is necessary.',
        'CRITICAL' => 'prepare for extended emergency conditions and maintain emergency readiness indefinitely.',
    ];
    return isset($preparations[$severity]) ? $preparations[$severity] : 'stay informed.';
}

function getAreaCheckInstructions($category) {
    $instructions = [
        'Weather Forecast' => 'Check if your barangay or district is within the weather warning area. Review the specific areas mentioned in the alert. Monitor local weather reports for your specific location.',
        'Emergency' => 'Check if your location is within the emergency zone. Review the affected areas listed in the notification. If you are in or near the affected area, follow emergency instructions immediately.',
        'Safety' => 'Review the geographical scope mentioned in the safety advisory. Check if your area falls within the affected zone. Follow local safety guidelines for your specific location.',
        'Announcement' => 'Review the announcement to see if it applies to your specific area or jurisdiction. Check if there are location-specific instructions or information.',
        'General' => 'Review the notification details to determine the geographical scope. Check if your location is mentioned or falls within the affected area.',
    ];
    return isset($instructions[$category]) ? $instructions[$category] : 'review the notification details.';
}

function getCategoryDetailedSafety($category, $severity) {
    $safety = [
        'Weather Forecast' => '1) Stay indoors during severe weather conditions. 2) Avoid flooded areas and do not attempt to cross floodwaters. 3) Secure your home and prepare emergency supplies (water, food, flashlight, first aid kit). 4) Follow evacuation orders immediately if issued. 5) Keep important documents in waterproof containers. 6) Stay informed through radio, TV, or official social media.',
        'Emergency' => '1) Prioritize personal safety above all else. 2) Follow emergency protocols and instructions from authorities. 3) Call emergency services (911) if in immediate danger. 4) Evacuate if instructed and use designated evacuation routes. 5) Help others only if it is safe to do so. 6) Stay informed through official emergency channels. 7) Keep emergency supplies ready and accessible.',
        'Safety' => '1) Review and implement all recommended precautions. 2) Follow health advisories and environmental guidelines. 3) Stay informed through official channels. 4) Report any safety concerns to authorities. 5) Prepare emergency supplies and know emergency contact numbers. 6) Share important safety information with family and neighbors.',
        'Announcement' => '1) Carefully review the announcement and understand its implications. 2) Follow any specific instructions provided. 3) Share important information with relevant parties. 4) Stay informed about any follow-up announcements. 5) Take appropriate action based on the announcement content.',
        'General' => '1) Review the notification content thoroughly. 2) Follow any specific instructions mentioned. 3) Stay informed about any updates. 4) Take appropriate action based on the information provided. 5) Share relevant information with others if needed.',
    ];
    
    $baseSafety = isset($safety[$category]) ? $safety[$category] : 'Follow official safety guidelines.';
    
    if ($severity === 'HIGH' || $severity === 'CRITICAL') {
        return '🚨 CRITICAL SAFETY: ' . $baseSafety . ' Given the high severity, take all safety measures seriously and act immediately.';
    } elseif ($severity === 'MEDIUM') {
        return '⚡ SAFETY PRECAUTIONS: ' . $baseSafety . ' Monitor the situation and be ready to implement additional safety measures if needed.';
    }
    return 'ℹ️ SAFETY INFORMATION: ' . $baseSafety . ' Stay informed and follow recommended guidelines.';
}