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
    // Generate mock response for now (simplified version)
    $response = generateMockResponse($input['message']);
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

function generateMockResponse($message) {
    $lowerMessage = strtolower($message);
    
    $patterns = [
        'alert_type' => ['type', 'category', 'kind', 'what type', 'what kind'],
        'severity' => ['severity', 'serious', 'urgent', 'critical', 'level', 'how serious'],
        'action' => ['what should i do', 'action', 'steps', 'guidelines', 'respond'],
        'source' => ['who sent', 'source', 'from', 'who issued', 'author'],
        'duration' => ['how long', 'duration', 'when will it end', 'how long will it last'],
        'area' => ['area', 'affected', 'location', 'where', 'my area'],
        'safety' => ['safety', 'safe', 'protect', 'guidelines', 'precautions'],
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

    switch ($intent) {
        case 'alert_type':
            $replyText = "This alert type indicates the nature of the emergency or notification. Categories include: Weather Forecast, Emergency, Safety, Announcement, and General.";
            break;
        case 'severity':
            $replyText = "Severity levels indicate urgency: HIGH - Immediate action required, MEDIUM - Monitor the situation, LOW - Informational only.";
            break;
        case 'action':
            $replyText = "For this notification: 1) Read the full alert details carefully, 2) Follow any specific instructions provided by authorities, 3) Stay tuned to official channels for updates.";
            break;
        case 'source':
            $replyText = "This notification was issued by official government emergency agencies including QCDRRMO, QCPD, QC Fire District.";
            break;
        case 'duration':
            $replyText = "Duration varies by alert type. Weather alerts typically last 6-24 hours, Emergency alerts remain active until resolved.";
            break;
        case 'area':
            $replyText = "This alert covers specific geographical areas mentioned in the notification. Check if your barangay or district is listed.";
            break;
        case 'safety':
            $replyText = "General safety guidelines: 1) Stay calm, 2) Follow official instructions, 3) Keep emergency supplies ready, 4) Know evacuation routes.";
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
