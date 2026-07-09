<?php
/**
 * UNIFIED CHAT & MESSAGING ENDPOINT
 * 
 * GET ?action=conversations: Fetch emergency conversation threads with citizens.
 * GET ?action=messages&conversation_id=<id>: Retrieve message logs of a specific conversation thread.
 * POST ?action=send: Append a new message/response into an active conversation thread.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/auth.php';

// Check if chat storage tables exist and are usable
function isChatStorageUsable(PDO $pdo): bool {
    try {
        $pdo->query("SELECT 1 FROM conversations LIMIT 1");
        $pdo->query("SELECT 1 FROM chat_messages LIMIT 1");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

$method = $_SERVER['REQUEST_METHOD'];

if (!isChatStorageUsable($pdo)) {
    logApiAccess($pdo, $deptName, '/api/chat.php', $method, 404, "Chat storage tables (conversations, chat_messages) not available in this local database");
    sendJsonResponse(false, 'Chat storage tables are currently unavailable or not configured in this database.', [], 404);
}

// Handle GET chat inquiries
if ($method === 'GET') {
    try {
        $action = isset($_GET['action']) ? trim($_GET['action']) : 'conversations';
        
        // Action 1: Get Conversation Lists
        if ($action === 'conversations') {
            $status = isset($_GET['status']) && $_GET['status'] !== '' ? trim($_GET['status']) : 'active';
            $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $offset = ($page - 1) * $limit;

            $query = "SELECT * FROM conversations WHERE 1=1";
            $countQuery = "SELECT COUNT(*) FROM conversations WHERE 1=1";
            $params = [];

            if ($status === 'active') {
                $query .= " AND status IN ('active', 'open', 'in_progress', 'waiting_user')";
                $countQuery .= " AND status IN ('active', 'open', 'in_progress', 'waiting_user')";
            } elseif ($status === 'closed') {
                $query .= " AND status IN ('closed', 'resolved')";
                $countQuery .= " AND status IN ('closed', 'resolved')";
            } elseif ($status !== 'all') {
                $query .= " AND status = ?";
                $countQuery .= " AND status = ?";
                $params[] = $status;
            }

            // Get Count
            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute($params);
            $totalRecords = (int)$countStmt->fetchColumn();

            // Get Data
            $query .= " ORDER BY last_message_time DESC, conversation_id DESC LIMIT ? OFFSET ?";
            $stmt = $pdo->prepare($query);
            
            $bindIdx = 1;
            foreach ($params as $paramVal) {
                $stmt->bindValue($bindIdx++, $paramVal, PDO::PARAM_STR);
            }
            $stmt->bindValue($bindIdx++, $limit, PDO::PARAM_INT);
            $stmt->bindValue($bindIdx++, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalPages = ceil($totalRecords / $limit);

            logApiAccess($pdo, $deptName, '/api/chat.php?action=conversations', 'GET', 200, "Retrieved " . count($conversations) . " conversations");
            sendJsonResponse(true, 'Conversations fetched successfully.', [
                'conversations' => $conversations,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total_records' => $totalRecords,
                    'total_pages' => $totalPages
                ]
            ]);
        }
        
        // Action 2: Get Messages for Conversation
        elseif ($action === 'messages') {
            $conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
            
            if ($conversationId <= 0) {
                sendJsonResponse(false, 'Bad Request: conversation_id parameter is required.', [], 400);
            }

            // Verify conversation thread exists
            $checkStmt = $pdo->prepare("SELECT conversation_id, status FROM conversations WHERE conversation_id = ? LIMIT 1");
            $checkStmt->execute([$conversationId]);
            if (!$checkStmt->fetch()) {
                logApiAccess($pdo, $deptName, "/api/chat.php?action=messages&conversation_id=$conversationId", 'GET', 404, "Conversation thread not found");
                sendJsonResponse(false, 'Conversation thread not found.', [], 404);
            }

            // Retrieve messages
            $stmt = $pdo->prepare("
                SELECT message_id, conversation_id, sender_id, sender_name, sender_type, message_text, is_read, created_at
                FROM chat_messages 
                WHERE conversation_id = ? 
                ORDER BY message_id ASC
            ");
            $stmt->execute([$conversationId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            logApiAccess($pdo, $deptName, "/api/chat.php?action=messages&conversation_id=$conversationId", 'GET', 200, "Retrieved " . count($messages) . " chat messages");
            sendJsonResponse(true, 'Chat messages retrieved successfully.', ['messages' => $messages]);
        } else {
            sendJsonResponse(false, 'Bad Request: Invalid action. Choose conversations or messages.', [], 400);
        }
    } catch (PDOException $e) {
        logApiAccess($pdo, $deptName, '/api/chat.php', 'GET', 500, "Database query failed: " . $e->getMessage());
        sendJsonResponse(false, 'Database query failed: ' . $e->getMessage(), [], 500);
    }
}

// Handle POST message dispatch
elseif ($method === 'POST') {
    try {
        $action = isset($_GET['action']) ? trim($_GET['action']) : '';
        if ($action !== 'send') {
            sendJsonResponse(false, 'Bad Request: Invalid action. Must specify ?action=send to POST messages.', [], 400);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        $conversationId = isset($input['conversation_id']) ? (int)$input['conversation_id'] : 0;
        $text = trim($input['text'] ?? '');
        $senderName = trim($input['sender_name'] ?? 'System Dispatcher (API)');

        if ($conversationId <= 0 || empty($text)) {
            sendJsonResponse(false, 'Bad Request: conversation_id and text fields are required.', [], 400);
        }

        // Verify conversation status
        $checkStmt = $pdo->prepare("SELECT conversation_id, status FROM conversations WHERE conversation_id = ? LIMIT 1");
        $checkStmt->execute([$conversationId]);
        $conversation = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$conversation) {
            logApiAccess($pdo, $deptName, '/api/chat.php?action=send', 'POST', 404, "Conversation thread $conversationId not found");
            sendJsonResponse(false, 'Conversation thread not found.', [], 404);
        }

        $status = strtolower($conversation['status'] ?? '');
        if ($status === 'closed' || $status === 'resolved') {
            sendJsonResponse(false, 'Forbidden: Cannot send a message to a closed or resolved conversation.', [], 403);
        }

        // Append message & update conversation thread state
        $pdo->beginTransaction();
        
        $insertStmt = $pdo->prepare("
            INSERT INTO chat_messages
            (conversation_id, sender_id, sender_name, sender_type, message_text, is_read, created_at)
            VALUES (?, ?, ?, 'admin', ?, 0, NOW())
        ");
        $insertStmt->execute([
            $conversationId,
            'api_' . strtolower(str_replace(' ', '_', $deptName)),
            $senderName,
            $text
        ]);
        $messageId = (int)$pdo->lastInsertId();

        // Update thread status to wait for user reply
        $updateStmt = $pdo->prepare("
            UPDATE conversations 
            SET last_message = ?, 
                last_message_time = NOW(), 
                updated_at = NOW(), 
                status = 'waiting_user' 
            WHERE conversation_id = ?
        ");
        $updateStmt->execute([$text, $conversationId]);

        $pdo->commit();

        logApiAccess($pdo, $deptName, '/api/chat.php?action=send', 'POST', 201, "Dispatched system message (ID: $messageId) to conversation thread $conversationId");
        sendJsonResponse(true, 'Message sent successfully.', [
            'message_id' => $messageId,
            'conversation_id' => $conversationId,
            'status' => 'waiting_user'
        ], 201);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        logApiAccess($pdo, $deptName, '/api/chat.php?action=send', 'POST', 500, "Database query exception: " . $e->getMessage());
        sendJsonResponse(false, 'Database operations failed: ' . $e->getMessage(), [], 500);
    }
} else {
    sendJsonResponse(false, 'Method Not Allowed.', [], 405);
}
