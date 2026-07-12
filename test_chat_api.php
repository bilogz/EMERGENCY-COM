<?php
// Test the chat-get-conversation API to see if it creates conversations
require_once 'USERS/api/db_connect.php';

if (!$pdo) {
    echo "PDO connection failed\n";
    exit(1);
}

echo "Testing conversation creation...\n";

// Simulate what the emergency call does
$userId = 1; // Test user ID
$userName = 'Test User';
$userEmail = 'test@example.com';
$userPhone = '1234567890';
$userLocation = 'Test Location';
$userConcern = 'emergency';
$isGuest = 0;

$params = http_build_query([
    'userId' => $userId,
    'userName' => $userName,
    'userEmail' => $userEmail,
    'userPhone' => $userPhone,
    'userLocation' => $userLocation,
    'userConcern' => $userConcern,
    'isGuest' => $isGuest
]);

$url = "USERS/api/chat-get-conversation.php?$params";
echo "Calling: $url\n";

// Use curl to test the API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/EMERGENCY-COM/$url");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

// Check if conversation was created
try {
    $stmt = $pdo->query("SELECT * FROM conversations WHERE user_concern = 'emergency' ORDER BY created_at DESC LIMIT 1");
    $conv = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($conv) {
        echo "\nLatest emergency conversation:\n";
        echo "ID: {$conv['conversation_id']}, User: {$conv['user_name']}, Concern: {$conv['user_concern']}, Status: {$conv['status']}\n";
        
        // Check if there are messages for this conversation
        $stmt2 = $pdo->prepare("SELECT * FROM chat_messages WHERE conversation_id = ? ORDER BY created_at DESC");
        $stmt2->execute([$conv['conversation_id']]);
        $msgs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        echo "Messages for this conversation: " . count($msgs) . "\n";
        if (count($msgs) > 0) {
            foreach ($msgs as $msg) {
                echo "  - Sender: {$msg['sender_type']}, Text: " . substr($msg['message_text'], 0, 50) . "...\n";
            }
        }
    } else {
        echo "\nNo emergency conversation found\n";
    }
} catch (PDOException $e) {
    echo "Error checking database: " . $e->getMessage() . "\n";
}
