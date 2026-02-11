<?php
/**
 * Two-way chat logic helpers shared by ADMIN/USERS APIs.
 * Keeps status flow, filters, routing, and read behavior consistent.
 */

if (!function_exists('twc_active_statuses')) {
    function twc_active_statuses(): array {
        return ['active', 'open', 'in_progress', 'waiting_user'];
    }
}

if (!function_exists('twc_closed_statuses')) {
    function twc_closed_statuses(): array {
        return ['closed', 'resolved'];
    }
}

if (!function_exists('twc_is_closed_status')) {
    function twc_is_closed_status($status): bool {
        $status = strtolower(trim((string)$status));
        return in_array($status, twc_closed_statuses(), true);
    }
}

if (!function_exists('twc_is_active_status')) {
    function twc_is_active_status($status): bool {
        $status = strtolower(trim((string)$status));
        return in_array($status, twc_active_statuses(), true);
    }
}

if (!function_exists('twc_normalize_status_input')) {
    function twc_normalize_status_input($status): ?string {
        $status = strtolower(trim((string)$status));
        if ($status === '') return null;

        $map = [
            'active' => 'open',
            'open' => 'open',
            'in_progress' => 'in_progress',
            'waiting_user' => 'waiting_user',
            'resolved' => 'resolved',
            'closed' => 'closed',
            'reopen' => 'open',
            'reopened' => 'open',
        ];

        return $map[$status] ?? null;
    }
}

if (!function_exists('twc_table_exists')) {
    function twc_table_exists(PDO $pdo, string $tableName): bool {
        static $cache = [];
        $key = strtolower($tableName);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        try {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = ?'
            );
            $stmt->execute([$tableName]);
            $cache[$key] = ((int)$stmt->fetchColumn()) > 0;
            return $cache[$key];
        } catch (Throwable $e) {
            $cache[$key] = false;
            return false;
        }
    }
}

if (!function_exists('twc_table_usable')) {
    /**
     * Checks whether a table can actually be queried.
     * Handles "table exists but doesn't exist in engine" corruption cases.
     */
    function twc_table_usable(PDO $pdo, string $tableName): bool {
        static $cache = [];
        $key = strtolower($tableName);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        if (!twc_table_exists($pdo, $tableName)) {
            $cache[$key] = false;
            return false;
        }

        try {
            $pdo->query("SELECT 1 FROM `$tableName` LIMIT 1");
            $cache[$key] = true;
            return true;
        } catch (Throwable $e) {
            error_log("TWC table unusable ($tableName): " . $e->getMessage());
            $cache[$key] = false;
            return false;
        }
    }
}

if (!function_exists('twc_chat_storage_available')) {
    /**
     * Minimal table health check required by chat APIs.
     */
    function twc_chat_storage_available(PDO $pdo): bool {
        return twc_table_usable($pdo, 'conversations') && twc_table_usable($pdo, 'chat_messages');
    }
}

if (!function_exists('twc_column_exists')) {
    function twc_column_exists(PDO $pdo, string $tableName, string $columnName): bool {
        static $cache = [];
        $key = strtolower($tableName . '.' . $columnName);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
            $stmt->execute([$columnName]);
            $cache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
            return $cache[$key];
        } catch (Throwable $e) {
            $cache[$key] = false;
            return false;
        }
    }
}

if (!function_exists('twc_placeholders')) {
    function twc_placeholders(array $values): string {
        return implode(',', array_fill(0, count($values), '?'));
    }
}

if (!function_exists('twc_status_filter_clause')) {
    /**
     * Returns SQL filter clause for conversation status.
     * Appends bind params into $params.
     */
    function twc_status_filter_clause(string $statusFilter, array &$params, string $alias = 'c'): string {
        $statusFilter = strtolower(trim($statusFilter));
        if ($statusFilter === '' || $statusFilter === 'all') {
            return '';
        }

        if ($statusFilter === 'assigned') {
            $active = twc_active_statuses();
            $params = array_merge($params, $active);
            return " AND {$alias}.status IN (" . twc_placeholders($active) . ") AND COALESCE({$alias}.assigned_to, 0) > 0 ";
        }

        if ($statusFilter === 'active' || $statusFilter === 'open') {
            $active = twc_active_statuses();
            $params = array_merge($params, $active);
            return " AND {$alias}.status IN (" . twc_placeholders($active) . ") ";
        }

        if ($statusFilter === 'closed') {
            $closed = twc_closed_statuses();
            $params = array_merge($params, $closed);
            return " AND {$alias}.status IN (" . twc_placeholders($closed) . ") ";
        }

        $params[] = $statusFilter;
        return " AND {$alias}.status = ? ";
    }
}

if (!function_exists('twc_normalize_category')) {
    function twc_normalize_category($raw): string {
        $value = strtolower(trim((string)$raw));
        if ($value === '') return '';

        $value = str_replace(['-', ' '], '_', $value);
        $map = [
            'emergency' => 'emergency_response',
            'response' => 'emergency_response',
            'emergency_response' => 'emergency_response',
            'traffic' => 'traffic_transport',
            'traffic_transport' => 'traffic_transport',
            'transport' => 'traffic_transport',
            'community' => 'community_policing',
            'community_policing' => 'community_policing',
            'policing' => 'community_policing',
            'crime' => 'crime_analytics',
            'crime_analytics' => 'crime_analytics',
            'public_safety' => 'public_safety_campaign',
            'public_safety_campaign' => 'public_safety_campaign',
            'health' => 'health_inspection',
            'health_inspection' => 'health_inspection',
            'disaster' => 'disaster_preparedness',
            'disaster_preparedness' => 'disaster_preparedness',
            'incident' => 'incident_nlp',
            'incident_nlp' => 'incident_nlp',
            'nlp' => 'incident_nlp',
            'multilingual' => 'emergency_comm',
            'communication' => 'emergency_comm',
            'emergency_comm' => 'emergency_comm',
        ];

        return $map[$value] ?? $value;
    }
}

if (!function_exists('twc_normalize_priority')) {
    function twc_normalize_priority($rawPriority, string $message = '', string $category = ''): string {
        $priority = strtolower(trim((string)$rawPriority));
        if ($priority === 'urgent' || $priority === 'normal') {
            return $priority;
        }

        $hay = strtolower($message . ' ' . $category);
        if (preg_match('/\b(urgent|emergency|life.?threat|fire|flood|earthquake|bomb|critical|help now|accident)\b/', $hay)) {
            return 'urgent';
        }
        return 'normal';
    }
}

if (!function_exists('twc_priority_rank')) {
    function twc_priority_rank(string $priority): int {
        return strtolower(trim($priority)) === 'urgent' ? 0 : 1;
    }
}

if (!function_exists('twc_pick_assignee')) {
    /**
     * Picks an active admin/staff as fallback assignee.
     * Returns admin_user.id or null if not available.
     */
    function twc_pick_assignee(PDO $pdo): ?int {
        if (!twc_table_exists($pdo, 'admin_user')) {
            return null;
        }
        try {
            $stmt = $pdo->query("
                SELECT id
                FROM admin_user
                WHERE status = 'active'
                  AND role IN ('staff', 'admin', 'super_admin')
                ORDER BY FIELD(role, 'staff', 'admin', 'super_admin'), COALESCE(last_login, '1970-01-01') DESC, id ASC
                LIMIT 1
            ");
            $id = $stmt->fetchColumn();
            return $id ? (int)$id : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('twc_ui_conversation_status')) {
    /**
     * Backward-compatible status for older UI that only understands active/closed.
     */
    function twc_ui_conversation_status($rawStatus): string {
        return twc_is_closed_status($rawStatus) ? 'closed' : 'active';
    }
}

if (!function_exists('twc_safe_int')) {
    function twc_safe_int($value): ?int {
        if ($value === null || $value === '') return null;
        return is_numeric($value) ? (int)$value : null;
    }
}

if (!function_exists('twc_status_for_db')) {
    /**
     * Maps workflow status to a status value supported by DB schema.
     * If conversations.status is ENUM and does not contain workflow values,
     * this safely falls back to active/closed.
     */
    function twc_status_for_db(PDO $pdo, string $desiredStatus): string {
        $desiredStatus = strtolower(trim($desiredStatus));
        if ($desiredStatus === '') {
            return 'active';
        }

        static $enumValues = null;
        if ($enumValues === null) {
            $enumValues = [];
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM conversations LIKE 'status'");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && isset($row['Type']) && stripos((string)$row['Type'], 'enum(') === 0) {
                    $type = (string)$row['Type'];
                    if (preg_match("/^enum\\((.*)\\)$/i", $type, $m)) {
                        $raw = $m[1];
                        $parts = str_getcsv($raw, ',', "'");
                        $enumValues = array_map('strtolower', array_map('trim', $parts));
                    }
                }
            } catch (Throwable $e) {
                $enumValues = [];
            }
        }

        // Non-enum status column: assume workflow values are accepted.
        if (empty($enumValues)) {
            return $desiredStatus;
        }

        if (in_array($desiredStatus, $enumValues, true)) {
            return $desiredStatus;
        }
        if (twc_is_closed_status($desiredStatus) && in_array('closed', $enumValues, true)) {
            return 'closed';
        }
        if (twc_is_active_status($desiredStatus) && in_array('active', $enumValues, true)) {
            return 'active';
        }
        if (in_array('active', $enumValues, true)) {
            return 'active';
        }
        return $desiredStatus;
    }
}
