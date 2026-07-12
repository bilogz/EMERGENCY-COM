<?php
mysqli_report(MYSQLI_REPORT_OFF);

$link = @mysqli_connect('127.0.0.1', 'root', '', null, 3306);
if (!$link) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error() . "\n";
    exit(1);
}

echo "=== Checking conversations table ===\n";
$result = mysqli_query($link, "SELECT COUNT(*) as total FROM conversations");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "Total conversations: " . $row['total'] . "\n";
    mysqli_free_result($result);
    
    $result = mysqli_query($link, "SELECT * FROM conversations ORDER BY created_at DESC LIMIT 5");
    if ($result) {
        echo "\nRecent conversations:\n";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "ID: {$row['conversation_id']}, User: {$row['user_name']}, Concern: {$row['user_concern']}, Status: {$row['status']}, Created: {$row['created_at']}\n";
        }
        mysqli_free_result($result);
    }
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}

echo "\n=== Checking chat_messages table ===\n";
$result = mysqli_query($link, "SELECT COUNT(*) as total FROM chat_messages");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "Total messages: " . $row['total'] . "\n";
    mysqli_free_result($result);
    
    $result = mysqli_query($link, "SELECT * FROM chat_messages ORDER BY created_at DESC LIMIT 5");
    if ($result) {
        echo "\nRecent messages:\n";
        while ($row = mysqli_fetch_assoc($result)) {
            $text = substr($row['message_text'], 0, 50);
            echo "ID: {$row['message_id']}, ConvID: {$row['conversation_id']}, Sender: {$row['sender_type']}, Text: {$text}..., Created: {$row['created_at']}\n";
        }
        mysqli_free_result($result);
    }
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}

echo "\n=== Checking for emergency-related conversations ===\n";
$result = mysqli_query($link, "SELECT * FROM conversations WHERE user_concern LIKE '%emergency%' OR user_concern LIKE '%call%' ORDER BY created_at DESC LIMIT 5");
if ($result) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "ID: {$row['conversation_id']}, User: {$row['user_name']}, Concern: {$row['user_concern']}, Status: {$row['status']}\n";
        }
    } else {
        echo "No emergency-related conversations found\n";
    }
    mysqli_free_result($result);
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}

mysqli_close($link);
