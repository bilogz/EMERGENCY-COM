<?php
require_once 'ADMIN/api/db_connect.php';

echo "=== Checking conversations table ===\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM conversations");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total conversations: " . $count['total'] . "\n";
    
    $stmt = $pdo->query("SELECT * FROM conversations ORDER BY created_at DESC LIMIT 5");
    $convs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nRecent conversations:\n";
    foreach ($convs as $conv) {
        echo "ID: {$conv['conversation_id']}, User: {$conv['user_name']}, Concern: {$conv['user_concern']}, Status: {$conv['status']}, Created: {$conv['created_at']}\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Checking chat_messages table ===\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chat_messages");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total messages: " . $count['total'] . "\n";
    
    $stmt = $pdo->query("SELECT * FROM chat_messages ORDER BY created_at DESC LIMIT 5");
    $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nRecent messages:\n";
    foreach ($msgs as $msg) {
        echo "ID: {$msg['message_id']}, ConvID: {$msg['conversation_id']}, Sender: {$msg['sender_type']}, Text: " . substr($msg['message_text'], 0, 50) . "..., Created: {$msg['created_at']}\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Checking for emergency-related conversations ===\n";
try {
    $stmt = $pdo->query("SELECT * FROM conversations WHERE user_concern LIKE '%emergency%' OR user_concern LIKE '%call%' ORDER BY created_at DESC LIMIT 5");
    $convs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($convs) > 0) {
        foreach ($convs as $conv) {
            echo "ID: {$conv['conversation_id']}, User: {$conv['user_name']}, Concern: {$conv['user_concern']}, Status: {$conv['status']}\n";
        }
    } else {
        echo "No emergency-related conversations found\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
