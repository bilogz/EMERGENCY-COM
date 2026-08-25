<?php
/**
 * Dialogflow API Endpoint
 * Handles AI-powered chatbot integration for notification assistance
 * Routes messages through Dialogflow for intent detection and response generation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'shared/db.php';
require_once 'shared/api_key.php';

// Validate API key
validateApiKey();

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

// Dialogflow configuration
$projectId = getenv('DIALOGFLOW_PROJECT_ID') ?: 'lgu-emergency-communication';
$privateKey = getenv('DIALOGFLOW_PRIVATE_KEY');
$clientEmail = getenv('DIALOGFLOW_CLIENT_EMAIL');

// If credentials not set in environment, use mock response
if (!$privateKey || !$clientEmail) {
    $response = generateMockResponse($input['message']);
    echo json_encode($response);
    exit;
}

try {
    // Generate session ID
    $sessionId = isset($input['sessionId']) ? $input['sessionId'] : 'session_' . time() . '_' . bin2hex(random_bytes(4));
    
    // Try to call Dialogflow API
    $response = callDialogflowAPI($projectId, $privateKey, $clientEmail, $sessionId, $input['message'], $input['context'] ?? []);
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Fallback to mock response on error
    error_log('Dialogflow API error: ' . $e->getMessage());
    $response = generateMockResponse($input['message']);
    echo json_encode($response);
}

/**
 * Call Dialogflow API using REST endpoint
 */
function callDialogflowAPI($projectId, $privateKey, $clientEmail, $sessionId, $message, $context = []) {
    // Generate JWT token for authentication
    $token = generateJWT($privateKey, $clientEmail);
    
    // Dialogflow API endpoint
    $url = "https://dialogflow.googleapis.com/v2/projects/{$projectId}/agent/sessions/{$sessionId}:detectIntent";
    
    // Prepare request body
    $queryParams = [
        'queryInput' => [
            'text' => [
                'text' => $message,
                'languageCode' => 'en-US'
            ]
        ]
    ];
    
    // Add context if provided
    if (!empty($context)) {
        $queryParams['queryParams'] = [
            'contexts' => array_map(function($key, $value) {
                return [
                    'name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/{$key}",
                    'lifespanCount' => 1,
                    'parameters' => $value
                ];
            }, array_keys($context), array_values($context))
        ];
    }
    
    // Make API request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($queryParams));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Dialogflow API returned HTTP {$httpCode}: {$response}");
    }
    
    $data = json_decode($response, true);
    
    // Extract response data
    $queryResult = $data['queryResult'] ?? [];
    $replyText = $queryResult['fulfillmentText'] ?? 'I apologize, but I could not process your request.';
    $intent = $queryResult['intent']['displayName'] ?? 'default';
    $confidence = $queryResult['intentDetectionConfidence'] ?? 0;
    
    // Check if escalation is needed based on intent
    $shouldEscalate = in_array($intent, ['emergency_critical', 'escalation_requested']) || 
                      $confidence < 0.5;
    
    return [
        'success' => true,
        'replyText' => $replyText,
        'intent' => $intent,
        'confidence' => $confidence,
        'shouldEscalate' => $shouldEscalate,
        'entities' => $queryResult['parameters'] ?? [],
        'sessionId' => $sessionId
    ];
}

/**
 * Generate JWT token for Dialogflow authentication
 */
function generateJWT($privateKey, $clientEmail) {
    // This is a simplified JWT generation
    // In production, use a proper JWT library like firebase/php-jwt
    
    $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
    $payload = json_encode([
        'iss' => $clientEmail,
        'sub' => $clientEmail,
        'aud' => 'https://dialogflow.googleapis.com/google.cloud.dialogflow.v2.AgentsClient',
        'iat' => time(),
        'exp' => time() + 3600
    ]);
    
    $headerEncoded = base64url_encode($header);
    $payloadEncoded = base64url_encode($payload);
    
    $signature = '';
    openssl_sign(
        $headerEncoded . '.' . $payloadEncoded,
        $signature,
        $privateKey,
        OPENSSL_ALGO_SHA256
    );
    
    $signatureEncoded = base64url_encode($signature);
    
    return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
}

/**
 * Base64 URL-safe encoding
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Generate mock response for development/fallback
 */
function generateMockResponse($message) {
    $lowerMessage = strtolower($message);
    
    // Notification-specific patterns
    $patterns = [
        'alert_type' => ['type', 'category', 'kind', 'what type', 'what kind', 'classification'],
        'severity' => ['severity', 'serious', 'urgent', 'critical', 'level', 'how serious'],
        'action' => ['what should i do', 'action', 'steps', 'guidelines', 'respond', 'react'],
        'source' => ['who sent', 'source', 'from', 'who issued', 'author'],
        'duration' => ['how long', 'duration', 'when will it end', 'how long will it last'],
        'area' => ['area', 'affected', 'location', 'where', 'my area'],
        'safety' => ['safety', 'safe', 'protect', 'guidelines', 'precautions'],
    ];
    
    // Detect intent
    $intent = 'general_info';
    $replyText = '';
    
    foreach ($patterns as $key => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($lowerMessage, $keyword) !== false) {
                $intent = $key;
                break 2;
            }
        }
    }
    
    // Generate response based on intent
    switch ($intent) {
        case 'alert_type':
            $replyText = "This notification type indicates the category of the alert. Based on the context, this appears to be a notification that requires your attention. The specific category helps you understand the nature of the alert.";
            break;
        case 'severity':
            $replyText = "Severity levels indicate the urgency of the alert: HIGH requires immediate action, MEDIUM means monitor the situation, and LOW is informational. This helps you prioritize your response.";
            break;
        case 'action':
            $replyText = "For this type of notification, you should: 1) Read the full details carefully, 2) Follow any specific instructions provided, 3) Stay informed through official channels, 4) Share important information with family if needed.";
            break;
        case 'source':
            $replyText = "This notification was issued by the official emergency communication system. Alerts are sent by authorized government agencies to ensure public safety.";
            break;
        case 'duration':
            $replyText = "The duration depends on the specific situation. Monitor official updates for the latest information. Stay prepared until the alert is officially lifted by authorities.";
            break;
        case 'area':
            $replyText = "This alert covers the affected areas mentioned in the notification details. Check if your location falls within the affected zone and follow local instructions accordingly.";
            break;
        case 'safety':
            $replyText = "General safety guidelines include: 1) Stay calm and informed, 2) Follow official instructions, 3) Have emergency supplies ready, 4) Know evacuation routes if applicable, 5) Keep communication lines open.";
            break;
        default:
            $replyText = "I can help you understand this notification. You can ask me about the alert type, severity level, recommended actions, source, duration, affected areas, or safety guidelines.";
            break;
    }
    
    return [
        'success' => true,
        'replyText' => $replyText,
        'intent' => $intent,
        'confidence' => 0.85,
        'shouldEscalate' => false,
        'sessionId' => 'session_' . time() . '_' . bin2hex(random_bytes(4))
    ];
}
