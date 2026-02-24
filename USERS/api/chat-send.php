<?php
/**
 * Send Chat Message API (User/Citizen)
 * User reply flow: creates/routs thread and keeps status in_progress.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/device_tracking.php';
require_once __DIR__ . '/../../ADMIN/api/chat-logic.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$savedAttachmentAbsolutePath = null;

try {
    $input = null;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
    }

    $text = trim((string)($input['text'] ?? $_POST['text'] ?? ''));
    $userId = $input['userId'] ?? $_POST['userId'] ?? null;
    $userName = trim((string)($input['userName'] ?? $_POST['userName'] ?? 'Guest User'));
    $userEmail = $input['userEmail'] ?? $_POST['userEmail'] ?? null;
    $userPhone = $input['userPhone'] ?? $_POST['userPhone'] ?? null;
    $userLocation = $input['userLocation'] ?? $_POST['userLocation'] ?? null;
    $userConcern = $input['userConcern'] ?? $_POST['userConcern'] ?? null;
    $rawCategory = $input['category'] ?? $_POST['category'] ?? $userConcern;
    $rawPriority = $input['priority'] ?? $_POST['priority'] ?? null;
    $isGuest = isset($input['isGuest'])
        ? ($input['isGuest'] === '1' || $input['isGuest'] === true)
        : (isset($_POST['isGuest']) ? ($_POST['isGuest'] === '1') : true);
    $conversationId = $input['conversationId'] ?? $_POST['conversationId'] ?? null;
    $conversationId = twc_safe_int($conversationId);
    $forceNewConversationRaw = $input['forceNewConversation'] ?? $_POST['forceNewConversation'] ?? null;
    $forceNewConversation = in_array(
        strtolower(trim((string)$forceNewConversationRaw)),
        ['1', 'true', 'yes', 'on'],
        true
    );
    if ($forceNewConversation) {
        $conversationId = null;
    }

    $ipAddress = function_exists('getClientIP') ? getClientIP() : ($_SERVER['REMOTE_ADDR'] ?? null);
    $deviceInfo = function_exists('formatDeviceInfoForDB') ? formatDeviceInfoForDB() : null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $attachmentUrl = null;
    $attachmentMime = null;
    $attachmentSize = null;
    $attachmentKind = null;
    $attachmentPreviewText = '[Attachment] File attached.';
    $attachmentPreviewTag = '[Attachment]';

    $uploadedFile = $_FILES['attachment'] ?? ($_FILES['photo'] ?? null);
    $hasAttachment = is_array($uploadedFile) && isset($uploadedFile['error']) && (int)$uploadedFile['error'] !== UPLOAD_ERR_NO_FILE;
    $maxUploadBytes = 100 * 1024 * 1024; // 100MB

    $parseIniBytes = static function (string $rawValue): int {
        $value = trim($rawValue);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int)$value;
        if ($number <= 0) {
            return 0;
        }

        switch ($unit) {
            case 'g':
                return $number * 1024 * 1024 * 1024;
            case 'm':
                return $number * 1024 * 1024;
            case 'k':
                return $number * 1024;
            default:
                return $number;
        }
    };

    $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
    if ($contentLength > 0 && $contentLength > ($maxUploadBytes + (2 * 1024 * 1024))) {
        throw new RuntimeException('Attachment must be 100MB or smaller.');
    }

    $isMultipartRequest = stripos($contentType, 'multipart/form-data') !== false;
    if ($isMultipartRequest && $contentLength > 0 && empty($_FILES) && empty($_POST)) {
        $uploadLimit = $parseIniBytes((string)ini_get('upload_max_filesize'));
        $postLimit = $parseIniBytes((string)ini_get('post_max_size'));
        $effectiveServerLimit = min(
            $uploadLimit > 0 ? $uploadLimit : PHP_INT_MAX,
            $postLimit > 0 ? $postLimit : PHP_INT_MAX
        );

        if ($effectiveServerLimit > 0 && $contentLength > $effectiveServerLimit) {
            throw new RuntimeException('Server upload limit is lower than 100MB. Set upload_max_filesize and post_max_size to at least 110M.');
        }
    }

    if ($hasAttachment) {
        if ((int)$uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Attachment upload failed. Please try again.');
        }

        $attachmentSize = (int)($uploadedFile['size'] ?? 0);
        if ($attachmentSize <= 0 || $attachmentSize > $maxUploadBytes) {
            throw new RuntimeException('Attachment must be 100MB or smaller.');
        }

        $tmpFile = (string)($uploadedFile['tmp_name'] ?? '');
        if ($tmpFile === '' || !is_uploaded_file($tmpFile)) {
            throw new RuntimeException('Invalid uploaded attachment.');
        }

        $originalName = isset($uploadedFile['name']) ? (string)$uploadedFile['name'] : '';
        $lowerName = strtolower($originalName);
        $isEmlByName = $lowerName !== '' && preg_match('/\.eml$/', $lowerName) === 1;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo ? (string)finfo_file($finfo, $tmpFile) : '';
        if ($finfo) {
            finfo_close($finfo);
        }
        $detectedMime = strtolower(trim($detectedMime));

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/ogg' => 'ogv',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'video/x-matroska' => 'mkv',
            'message/rfc822' => 'eml',
            'application/eml' => 'eml',
        ];

        $resolvedExtension = $allowedTypes[$detectedMime] ?? null;
        if ($resolvedExtension === null && $isEmlByName) {
            $resolvedExtension = 'eml';
            $detectedMime = 'message/rfc822';
        }
        if ($resolvedExtension === null) {
            throw new RuntimeException('Only image, video, or .eml email attachments are allowed.');
        }

        if (strpos($detectedMime, 'image/') === 0) {
            $attachmentKind = 'image';
            $attachmentPreviewTag = '[Photo]';
            $attachmentPreviewText = '[Photo] Incident proof attached.';
        } elseif (strpos($detectedMime, 'video/') === 0) {
            $attachmentKind = 'video';
            $attachmentPreviewTag = '[Video]';
            $attachmentPreviewText = '[Video] Incident video attached.';
        } elseif ($resolvedExtension === 'eml') {
            $attachmentKind = 'email';
            $attachmentPreviewTag = '[Email]';
            $attachmentPreviewText = '[Email] Incident email attached.';
        } else {
            $attachmentKind = 'file';
        }

        $storageDriver = twc_chat_image_storage_driver();
        if ($storageDriver === 'postgres') {
            $stored = twc_store_attachment_in_postgres(
                $tmpFile,
                $detectedMime,
                $attachmentSize,
                $originalName !== '' ? $originalName : null
            );
            if ($stored && !empty($stored['url'])) {
                $attachmentMime = (string)($stored['mime'] ?? $detectedMime);
                $attachmentSize = isset($stored['size']) ? (int)$stored['size'] : $attachmentSize;
                $attachmentUrl = (string)$stored['url'];
            } else {
                error_log('chat-send: PostgreSQL attachment storage unavailable, falling back to filesystem storage.');
                $storageDriver = 'filesystem';
            }
        }

        if ($storageDriver === 'filesystem') {
            $uploadDir = twc_chat_upload_dir();
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                throw new RuntimeException('Unable to create upload directory.');
            }
            if (!is_writable($uploadDir)) {
                @chmod($uploadDir, 0755);
                clearstatcache(true, $uploadDir);
                if (!is_writable($uploadDir)) {
                    @chmod($uploadDir, 0775);
                    clearstatcache(true, $uploadDir);
                }
            }

            $newFileName = 'incident_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $resolvedExtension;
            $savedAttachmentAbsolutePath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

            $savedToDisk = false;
            $lastUploadWarning = null;

            if (move_uploaded_file($tmpFile, $savedAttachmentAbsolutePath)) {
                $savedToDisk = true;
            } else {
                $lastUploadWarning = error_get_last();
                clearstatcache(true, $tmpFile);

                // Fallbacks for hosts where move_uploaded_file() fails despite a valid upload.
                if (is_file($tmpFile) && @rename($tmpFile, $savedAttachmentAbsolutePath)) {
                    $savedToDisk = true;
                } elseif (is_file($tmpFile) && @copy($tmpFile, $savedAttachmentAbsolutePath)) {
                    @unlink($tmpFile);
                    $savedToDisk = true;
                } else {
                    $lastUploadWarning = error_get_last();
                }
            }

            if (!$savedToDisk) {
                // Last-resort fallback: store attachment in PostgreSQL when disk writes fail.
                $storedFallback = twc_store_attachment_in_postgres(
                    $tmpFile,
                    $detectedMime,
                    $attachmentSize,
                    $originalName !== '' ? $originalName : null
                );
                if ($storedFallback && !empty($storedFallback['url'])) {
                    $attachmentMime = (string)($storedFallback['mime'] ?? $detectedMime);
                    $attachmentSize = isset($storedFallback['size']) ? (int)$storedFallback['size'] : $attachmentSize;
                    $attachmentUrl = (string)$storedFallback['url'];
                    $savedAttachmentAbsolutePath = null;
                    error_log('chat-send: filesystem attachment save failed; fell back to PostgreSQL storage.');
                    $savedToDisk = true;
                }
            }

            if (!$savedToDisk) {
                $debugContext = [
                    'storageDriver' => $storageDriver,
                    'tmpFile' => $tmpFile,
                    'tmpFileExists' => is_file($tmpFile),
                    'uploadDir' => $uploadDir,
                    'uploadDirWritable' => is_writable($uploadDir),
                    'destination' => $savedAttachmentAbsolutePath,
                    'pdoPgsqlLoaded' => extension_loaded('pdo_pgsql'),
                    'pgImgUrlConfigured' => trim((string)twc_secure_cfg('PG_IMG_URL', '')) !== '',
                    'pgImgHostConfigured' => trim((string)twc_secure_cfg('PG_IMG_HOST', '')) !== '',
                    'pgImgDbConfigured' => trim((string)twc_secure_cfg('PG_IMG_DB', '')) !== '',
                    'pgImgUserConfigured' => trim((string)twc_secure_cfg('PG_IMG_USER', '')) !== '',
                ];
                if (is_array($lastUploadWarning) && !empty($lastUploadWarning['message'])) {
                    $debugContext['phpWarning'] = $lastUploadWarning['message'];
                }
                error_log('chat-send: attachment save failure context: ' . json_encode($debugContext));
                throw new RuntimeException('Failed to save uploaded attachment.');
            }

            $attachmentMime = $detectedMime !== '' ? $detectedMime : 'application/octet-stream';
            $attachmentUrl = twc_chat_upload_url($newFileName);
        }
    }

    $cleanupAttachmentOnEarlyExit = static function () use (&$savedAttachmentAbsolutePath): void {
        if ($savedAttachmentAbsolutePath && file_exists($savedAttachmentAbsolutePath)) {
            @unlink($savedAttachmentAbsolutePath);
        }
    };

    if ($text === '' && !$hasAttachment) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message text or attachment is required']);
        exit;
    }

    if (empty($userId)) {
        $cleanupAttachmentOnEarlyExit();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }

    $category = twc_normalize_category($rawCategory ?? '');
    $messageForPriority = $text !== '' ? $text : $attachmentPreviewText;
    $priority = twc_normalize_priority($rawPriority ?? '', $messageForPriority, $category);
    $storedMessageText = $text !== '' ? $text : $attachmentPreviewText;
    $lastMessagePreview = $text !== '' ? $text : $attachmentPreviewText;
    if ($attachmentUrl !== null && $text !== '') {
        $lastMessagePreview = $text . ' ' . $attachmentPreviewTag;
    }

    $hasCategoryColumn = twc_column_exists($pdo, 'conversations', 'category');
    $hasPriorityColumn = twc_column_exists($pdo, 'conversations', 'priority');
    $hasUserIdStringColumn = twc_column_exists($pdo, 'conversations', 'user_id_string');
    $hasAssignedToColumn = twc_column_exists($pdo, 'conversations', 'assigned_to');
    $hasAttachmentColumns = twc_ensure_chat_attachment_columns($pdo);
    $statusOpen = twc_status_for_db($pdo, 'open');
    $statusInProgress = twc_status_for_db($pdo, 'in_progress');

    if ($attachmentUrl !== null && !$hasAttachmentColumns) {
        // Production fallback: if DB user cannot alter chat_messages, keep chat flow working.
        // Preserve proof by embedding URL into message text until schema is updated.
        $inlineAttachmentNote = trim($attachmentPreviewTag . ' ' . $attachmentUrl);
        if ($text !== '') {
            $storedMessageText = $text . PHP_EOL . $inlineAttachmentNote;
            $lastMessagePreview = $text . ' ' . $attachmentPreviewTag;
        } else {
            $storedMessageText = $attachmentPreviewText . ' ' . $attachmentUrl;
            $lastMessagePreview = $attachmentPreviewText;
        }
        error_log('chat-send: attachment columns missing; stored attachment URL inline in message_text');
    }

    if (!empty($conversationId)) {
        $statusStmt = $pdo->prepare("SELECT status FROM conversations WHERE conversation_id = ?");
        $statusStmt->execute([$conversationId]);
        $conversation = $statusStmt->fetch(PDO::FETCH_ASSOC);
        if (!$conversation) {
            // Stale client-side conversation id; auto-create/reuse active thread below.
            $conversationId = null;
        } elseif (twc_is_closed_status($conversation['status'] ?? '')) {
            $cleanupAttachmentOnEarlyExit();
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'This conversation is closed. Please start a new conversation.',
                'conversationStatus' => 'closed'
            ]);
            exit;
        }
    }

    $pdo->beginTransaction();

    $statusActive = twc_active_statuses();
    $statusInClause = twc_placeholders($statusActive);
    $existingConv = null;

    if (empty($conversationId)) {
        if ($forceNewConversation) {
            // Force a fresh thread for incident reporting; close any active threads for this actor.
            $closeIds = [];
            $collectIds = static function (array &$into, array $rows): void {
                foreach ($rows as $row) {
                    $cid = isset($row['conversation_id']) ? (int)$row['conversation_id'] : 0;
                    if ($cid > 0) {
                        $into[$cid] = true;
                    }
                }
            };

            if ($hasUserIdStringColumn && !is_numeric($userId)) {
                $sql = "
                    SELECT conversation_id
                    FROM conversations
                    WHERE user_id_string = ?
                      AND status IN ($statusInClause)
                ";
                $params = [$userId];
                $params = array_merge($params, $statusActive);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $collectIds($closeIds, $stmt->fetchAll(PDO::FETCH_ASSOC));
            } elseif (is_numeric($userId)) {
                $sql = "
                    SELECT conversation_id
                    FROM conversations
                    WHERE user_id = ?
                      AND status IN ($statusInClause)
                ";
                $params = [$userId];
                $params = array_merge($params, $statusActive);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $collectIds($closeIds, $stmt->fetchAll(PDO::FETCH_ASSOC));
            }

            if ($isGuest && $ipAddress && $deviceInfo) {
                $sql = "
                    SELECT conversation_id
                    FROM conversations
                    WHERE ip_address = ?
                      AND device_info = ?
                      AND status IN ($statusInClause)
                ";
                $params = [$ipAddress, $deviceInfo];
                $params = array_merge($params, $statusActive);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $collectIds($closeIds, $stmt->fetchAll(PDO::FETCH_ASSOC));
            }

            $closeConversationIds = array_map('intval', array_keys($closeIds));
            if (!empty($closeConversationIds)) {
                $statusClosed = twc_status_for_db($pdo, 'closed');
                $closedByLabel = 'Citizen/User';
                $closeWhere = twc_placeholders($closeConversationIds);
                $setParts = ["status = ?", "last_message = CONCAT('Closed by ', ?)", "updated_at = NOW()"];
                $setParams = [$statusClosed, $closedByLabel];

                if (twc_column_exists($pdo, 'conversations', 'closed_by')) {
                    $setParts[] = 'closed_by = ?';
                    $setParams[] = $closedByLabel;
                }

                $closeSql = "UPDATE conversations SET " . implode(', ', $setParts) . " WHERE conversation_id IN ($closeWhere)";
                $closeStmt = $pdo->prepare($closeSql);
                $closeStmt->execute(array_merge($setParams, $closeConversationIds));

                if (twc_table_exists($pdo, 'chat_queue')) {
                    $queueStatusClosed = twc_status_for_db($pdo, 'closed');
                    $queueSql = "UPDATE chat_queue SET status = ?, updated_at = NOW() WHERE conversation_id IN ($closeWhere)";
                    $queueStmt = $pdo->prepare($queueSql);
                    $queueStmt->execute(array_merge([$queueStatusClosed], $closeConversationIds));
                }
            }
        } elseif ($hasUserIdStringColumn && !is_numeric($userId)) {
            $sql = "
                SELECT conversation_id, assigned_to
                FROM conversations
                WHERE user_id_string = ?
                  AND status IN ($statusInClause)
                ORDER BY updated_at DESC, conversation_id DESC
                LIMIT 1
            ";
            $params = [$userId];
            $params = array_merge($params, $statusActive);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $existingConv = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // If user_id_string is unavailable and userId is non-numeric,
            // skip user_id matching to avoid guest collisions on implicit 0 casts.
            if (is_numeric($userId)) {
                $sql = "
                    SELECT conversation_id, assigned_to
                    FROM conversations
                    WHERE user_id = ?
                      AND status IN ($statusInClause)
                    ORDER BY updated_at DESC, conversation_id DESC
                    LIMIT 1
                ";
                $params = [$userId];
                $params = array_merge($params, $statusActive);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $existingConv = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }

        if (
            !$forceNewConversation &&
            !$existingConv &&
            $isGuest &&
            $ipAddress &&
            $deviceInfo
        ) {
            $sql = "
                SELECT conversation_id, assigned_to
                FROM conversations
                WHERE ip_address = ?
                  AND device_info = ?
                  AND status IN ($statusInClause)
                ORDER BY updated_at DESC, conversation_id DESC
                LIMIT 1
            ";
            $params = [$ipAddress, $deviceInfo];
            $params = array_merge($params, $statusActive);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $existingConv = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($existingConv) {
            $conversationId = (int)$existingConv['conversation_id'];
        } else {
            $allowedProofKinds = ['image', 'video'];
            if (!$hasAttachment || !in_array($attachmentKind, $allowedProofKinds, true)) {
                throw new RuntimeException('Incident photo or video proof is required before starting a new conversation.');
            }

            $fallbackAssignee = $hasAssignedToColumn ? twc_pick_assignee($pdo) : null;

            $columns = [
                'user_id',
                'user_name',
                'user_email',
                'user_phone',
                'user_location',
                'user_concern',
                'is_guest',
                'device_info',
                'ip_address',
                'user_agent',
                'status',
                'created_at',
                'updated_at',
            ];
            $values = [
                !is_numeric($userId) ? 0 : $userId,
                $userName,
                $userEmail,
                $userPhone,
                $userLocation,
                $category !== '' ? $category : $userConcern,
                $isGuest ? 1 : 0,
                $deviceInfo,
                $ipAddress,
                $userAgent,
                $statusOpen,
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
            ];

            if ($hasUserIdStringColumn) {
                $columns[] = 'user_id_string';
                $values[] = is_numeric($userId) ? null : (string)$userId;
            }
            if ($hasAssignedToColumn && $fallbackAssignee !== null) {
                $columns[] = 'assigned_to';
                $values[] = $fallbackAssignee;
            }
            if ($hasCategoryColumn) {
                $columns[] = 'category';
                $values[] = $category !== '' ? $category : null;
            }
            if ($hasPriorityColumn) {
                $columns[] = 'priority';
                $values[] = $priority;
            }

            $insertSql = "INSERT INTO conversations (" . implode(',', $columns) . ")
                          VALUES (" . twc_placeholders($values) . ")";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute($values);
            $conversationId = (int)$pdo->lastInsertId();
        }
    }

    $insertColumns = [
        'conversation_id',
        'sender_id',
        'sender_name',
        'sender_type',
        'message_text',
        'ip_address',
        'device_info',
        'is_read',
        'created_at',
    ];
    $insertValues = [
        $conversationId,
        (string)$userId,
        $userName,
        'user',
        $storedMessageText,
        $ipAddress,
        $deviceInfo,
        0,
        date('Y-m-d H:i:s'),
    ];

    if ($hasAttachmentColumns) {
        $insertColumns[] = 'attachment_url';
        $insertValues[] = $attachmentUrl;
        $insertColumns[] = 'attachment_mime';
        $insertValues[] = $attachmentMime;
        $insertColumns[] = 'attachment_size';
        $insertValues[] = $attachmentSize;
    }

    $insertMessageSql = "INSERT INTO chat_messages (" . implode(', ', $insertColumns) . ")
        VALUES (" . twc_placeholders($insertValues) . ")";
    $insertMessageStmt = $pdo->prepare($insertMessageSql);
    $insertMessageStmt->execute($insertValues);
    $messageId = (int)$pdo->lastInsertId();

    $convAssignStmt = $pdo->prepare("SELECT assigned_to FROM conversations WHERE conversation_id = ? LIMIT 1");
    $convAssignStmt->execute([$conversationId]);
    $convNow = $convAssignStmt->fetch(PDO::FETCH_ASSOC);
    $assignedTo = twc_safe_int($convNow['assigned_to'] ?? null);
    if ($assignedTo === null && $hasAssignedToColumn) {
        $assignedTo = twc_pick_assignee($pdo);
    }

    $updateParts = [
        "last_message = ?",
        "last_message_time = NOW()",
        "updated_at = NOW()",
        "status = ?",
        "user_concern = COALESCE(?, user_concern)",
    ];
    $updateParams = [
        $lastMessagePreview,
        $statusInProgress,
        $category !== '' ? $category : $userConcern,
    ];

    if ($hasAssignedToColumn && $assignedTo !== null) {
        $updateParts[] = "assigned_to = ?";
        $updateParams[] = $assignedTo;
    }
    if ($hasCategoryColumn && $category !== '') {
        $updateParts[] = "category = ?";
        $updateParams[] = $category;
    }
    if ($hasPriorityColumn) {
        $updateParts[] = "priority = ?";
        $updateParams[] = $priority;
    }

    $updateParams[] = $conversationId;
    $updateSql = "UPDATE conversations SET " . implode(', ', $updateParts) . " WHERE conversation_id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute($updateParams);

    if (twc_table_exists($pdo, 'chat_queue')) {
        $queueHasAssigned = twc_column_exists($pdo, 'chat_queue', 'assigned_to');

        $queueColumns = [
            'conversation_id', 'user_id', 'user_name', 'user_email', 'user_phone',
            'user_location', 'user_concern', 'is_guest', 'message', 'status', 'created_at'
        ];
        $queueValues = [
            $conversationId, (string)$userId, $userName, $userEmail, $userPhone,
            $userLocation, ($category !== '' ? $category : $userConcern), $isGuest ? 1 : 0, $lastMessagePreview, 'pending', date('Y-m-d H:i:s')
        ];
        if ($queueHasAssigned) {
            $queueColumns[] = 'assigned_to';
            $queueValues[] = $assignedTo;
        }

        $queueSql = "INSERT INTO chat_queue (" . implode(',', $queueColumns) . ")
                     VALUES (" . twc_placeholders($queueValues) . ")
                     ON DUPLICATE KEY UPDATE
                        message = VALUES(message),
                        status = 'pending',
                        updated_at = NOW()";
        if ($queueHasAssigned) {
            $queueSql .= ", assigned_to = VALUES(assigned_to)";
        }
        $queueStmt = $pdo->prepare($queueSql);
        $queueStmt->execute($queueValues);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'messageId' => $messageId,
        'conversationId' => $conversationId,
        'workflowStatus' => $statusInProgress,
        'category' => $category,
        'priority' => $priority,
        'assignedTo' => $assignedTo,
        'imageUrl' => $attachmentUrl,
        'attachment' => $attachmentUrl !== null ? [
            'url' => $attachmentUrl,
            'mime' => $attachmentMime,
            'size' => $attachmentSize,
            'type' => $attachmentKind,
        ] : null,
        'message' => 'Message sent successfully'
    ]);
} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($savedAttachmentAbsolutePath && file_exists($savedAttachmentAbsolutePath)) {
        @unlink($savedAttachmentAbsolutePath);
    }
    error_log('Chat send error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($savedAttachmentAbsolutePath && file_exists($savedAttachmentAbsolutePath)) {
        @unlink($savedAttachmentAbsolutePath);
    }
    error_log('Chat send general error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage() ?: 'An error occurred']);
}
