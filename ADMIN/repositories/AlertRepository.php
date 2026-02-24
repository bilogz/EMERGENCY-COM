<?php

/**
 * Alert Repository
 * Handles all database operations for alerts
 *
 * @package ADMIN\Repositories
 */

require_once __DIR__ . '/../api/db_connect.php';

class AlertRepository
{
    private $pdo;
    private $alertsColumns = null;

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    private function getAlertsColumns()
    {
        if (is_array($this->alertsColumns)) {
            return $this->alertsColumns;
        }

        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM alerts");
            $this->alertsColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->alertsColumns = [];
        }

        return $this->alertsColumns;
    }
    
    /**
     * Create a new alert
     *
     * @param string $title Alert title
     * @param string $message Alert message
     * @param string|null $content Alert content (optional, defaults to message)
     * @param int|null $categoryId Category ID (optional)
     * @param string $status Alert status (default: 'active')
     * @param string|null $severity Alert severity (optional)
     * @param int|null $weatherSignal Weather signal (1-5) for weather category (optional)
     * @param int|null $fireLevel Fire level (1-3) for fire category (optional)
     * @param string|null $source Alert source (optional, e.g. mass_notification, pagasa, phivolcs)
     * @return int|null New alert ID or null on failure
     */
    public function create($title, $message, $content = null, $categoryId = null, $status = 'active', $severity = null, $weatherSignal = null, $fireLevel = null, $source = null)
    {
        if ($content === null) {
            $content = $message;
        }
        
        try {
            $cols = ['title', 'message', 'content', 'category_id', 'status'];
            $vals = [$title, $message, $content, $categoryId, $status];
            $placeholders = array_fill(0, count($vals), '?');

            $available = $this->getAlertsColumns();

            if ($severity !== null && in_array('severity', $available, true)) {
                $cols[] = 'severity';
                $vals[] = $severity;
                $placeholders[] = '?';
            }

            if ($weatherSignal !== null && in_array('weather_signal', $available, true)) {
                $cols[] = 'weather_signal';
                $vals[] = $weatherSignal;
                $placeholders[] = '?';
            }

            if ($fireLevel !== null && in_array('fire_level', $available, true)) {
                $cols[] = 'fire_level';
                $vals[] = $fireLevel;
                $placeholders[] = '?';
            }

            if ($source !== null && $source !== '' && in_array('source', $available, true)) {
                $cols[] = 'source';
                $vals[] = $source;
                $placeholders[] = '?';
            }

            $cols[] = 'created_at';
            $placeholders[] = 'NOW()';

            $stmt = $this->pdo->prepare("INSERT INTO alerts (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")");
            $stmt->execute($vals);
            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating alert: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get alert by ID
     *
     * @param int $alertId Alert ID
     * @return array|null Alert data or null if not found
     */
    public function getById($alertId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT a.*, ac.name as category_name
                FROM alerts a
                LEFT JOIN alert_categories ac ON ac.id = a.category_id
                WHERE a.id = ?
            ");
            $stmt->execute([$alertId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error getting alert by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all alerts with optional filters
     *
     * @param array $filters Optional filters: status, category_id, limit, offset
     * @return array Array of alert records
     */
    public function getAll($filters = [])
    {
        $status = $filters['status'] ?? null;
        $categoryId = $filters['category_id'] ?? null;
        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;
        $orderBy = $filters['order_by'] ?? 'created_at DESC';
        
        try {
            $sql = "
                SELECT a.*, ac.name as category_name
                FROM alerts a
                LEFT JOIN alert_categories ac ON ac.id = a.category_id
                WHERE 1=1
            ";
            $params = [];
            
            if ($status) {
                $sql .= " AND a.status = ?";
                $params[] = $status;
            }
            
            if ($categoryId) {
                $sql .= " AND a.category_id = ?";
                $params[] = $categoryId;
            }
            
            // Safe order by - only allow specific columns
            $allowedOrderColumns = ['created_at', 'updated_at', 'id', 'title'];
            $orderParts = explode(' ', $orderBy);
            $orderColumn = $orderParts[0] ?? 'created_at';
            $orderDirection = strtoupper($orderParts[1] ?? 'DESC');
            
            if (!in_array($orderColumn, $allowedOrderColumns, true)) {
                $orderColumn = 'created_at';
            }
            if ($orderDirection !== 'ASC' && $orderDirection !== 'DESC') {
                $orderDirection = 'DESC';
            }
            
            $sql .= " ORDER BY a." . $orderColumn . " " . $orderDirection . " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting alerts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get alerts count with optional filters
     *
     * @param array $filters Optional filters: status, category_id
     * @return int Total count
     */
    public function getCount($filters = [])
    {
        $status = $filters['status'] ?? null;
        $categoryId = $filters['category_id'] ?? null;
        
        try {
            $sql = "SELECT COUNT(*) FROM alerts WHERE 1=1";
            $params = [];
            
            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            if ($categoryId) {
                $sql .= " AND category_id = ?";
                $params[] = $categoryId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting alerts count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Update alert status
     *
     * @param int $alertId Alert ID
     * @param string $status New status
     * @return bool True on success, false on failure
     */
    public function updateStatus($alertId, $status)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE alerts 
                SET status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$status, $alertId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error updating alert status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find or get default category ID
     *
     * @param string $categoryName Category name (default: 'General')
     * @return int|null Category ID or null if not found
     */
    public function findOrGetDefaultCategoryId($categoryName = 'General')
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM alert_categories WHERE name = ? LIMIT 1");
            $stmt->execute([$categoryName]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            return $category ? (int)$category['id'] : null;
        } catch (PDOException $e) {
            error_log("Error finding category: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get active alerts for users (optimized query with category info)
     *
     * @param array $filters Optional filters: category, area, lastId, lastUpdate, limit
     * @return array Array of alert records with category info
     */
    public function getActiveAlertsForUsers($filters = [])
    {
        $category = $filters['category'] ?? null;
        $area = $filters['area'] ?? null;
        $lastId = $filters['lastId'] ?? 0;
        $lastUpdate = $filters['lastUpdate'] ?? null;
        $limit = $filters['limit'] ?? 50;
        $status = $filters['status'] ?? 'active';

        try {
            $sql = "
                SELECT 
                    a.id,
                    a.title,
                    a.message,
                    a.content,
                    a.status,
                    a.created_at,
                    a.updated_at,
                    COALESCE(ac.name, 'General') as category_name,
                    COALESCE(ac.icon, 'fa-exclamation-triangle') as category_icon,
                    COALESCE(ac.color, '#95a5a6') as category_color
            ";

            // Check if area column exists (dynamic query building)
            $hasAreaColumn = false;
            try {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM alerts LIKE 'area'");
                $hasAreaColumn = $stmt->rowCount() > 0;
                if ($hasAreaColumn) {
                    $sql .= ", a.area";
                    $stmt = $this->pdo->query("SHOW COLUMNS FROM alerts LIKE 'category'");
                    if ($stmt->rowCount() > 0) {
                        $sql .= ", a.category";
                    }
                }
            } catch (PDOException $e) {
                // Column check failed, continue without area
            }

            $sql .= "
                FROM alerts a
                LEFT JOIN alert_categories ac ON a.category_id = ac.id
                WHERE a.status = :status
            ";

            $params = [':status' => $status];

            // Filter by area if provided and column exists
            if ($area && $hasAreaColumn) {
                $sql .= " AND (a.area = :area OR a.area IS NULL OR a.area = '')";
                $params[':area'] = $area;
            }

            // Filter by category if provided
            if ($category && $category !== 'all') {
                $sql .= " AND (ac.name = :category OR (:category = 'General' AND ac.name IS NULL))";
                $params[':category'] = $category;
            }

            // Get only new alerts if lastId is provided
            if ($lastId > 0) {
                $sql .= " AND a.id > :last_id";
                $params[':last_id'] = $lastId;
            }

            // Alternative: check by updated_at timestamp
            if ($lastUpdate && $lastId == 0) {
                $lastUpdateTime = date('Y-m-d H:i:s', strtotime($lastUpdate));
                $sql .= " AND a.updated_at > :last_update";
                $params[':last_update'] = $lastUpdateTime;
            }

            $sql .= " ORDER BY a.created_at DESC, a.id DESC LIMIT :limit";
            $params[':limit'] = (int)$limit;

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting active alerts for users: " . $e->getMessage());
            return [];
        }
    }
}
