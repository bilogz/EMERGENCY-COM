<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'shared/db_connect.php';
require_once 'shared/apiResponse.php';

/** @var PDO $pdo */

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // Submit crime tip - handle both JSON and FormData
        $data = [];
        
        // Check if this is a FormData upload (has files)
        if (!empty($_FILES)) {
            // FormData submission
            $data = $_POST;
        } else {
            // JSON submission
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (!is_array($data)) {
                apiResponse::error("Invalid JSON input.", 400);
            }
        }

        $crime_type = trim((string)($data['crime_type'] ?? ''));
        $location = trim((string)($data['location'] ?? ''));
        $date_of_crime = $data['date_of_crime'] ?? null;
        $details = trim((string)($data['details'] ?? ''));

        // Validate required fields
        if (empty($crime_type)) {
            apiResponse::error("Missing required field: crime_type", 400);
        }
        if (empty($location)) {
            apiResponse::error("Missing required field: location", 400);
        }
        if (empty($details)) {
            apiResponse::error("Missing required field: details", 400);
        }

        // Validate date format if provided
        if ($date_of_crime !== null) {
            // Try to parse the date
            $dateTime = DateTime::createFromFormat('Y-m-d\TH:i', $date_of_crime);
            if (!$dateTime) {
                $dateTime = DateTime::createFromFormat('Y-m-d H:i', $date_of_crime);
            }
            if ($dateTime) {
                $date_of_crime = $dateTime->format('Y-m-d H:i:s');
            } else {
                $date_of_crime = null; // Invalid date, set to null
            }
        }

        // Sanitize inputs
        $crime_type = htmlspecialchars($crime_type, ENT_QUOTES, 'UTF-8');
        $location = htmlspecialchars($location, ENT_QUOTES, 'UTF-8');
        $details = htmlspecialchars($details, ENT_QUOTES, 'UTF-8');

        // Check if crime_tips table exists, create if not
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'crime_tips'");
        if ($tableCheck->rowCount() == 0) {
            $createTable = "
                CREATE TABLE crime_tips (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    crime_type VARCHAR(100) NOT NULL,
                    location VARCHAR(255) NOT NULL,
                    date_of_crime DATETIME NULL,
                    details TEXT NOT NULL,
                    image_path VARCHAR(255) NULL,
                    status VARCHAR(50) DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_status (status),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $pdo->exec($createTable);
        } else {
            // Check if image_path column exists, add if not
            $columnCheck = $pdo->query("SHOW COLUMNS FROM crime_tips LIKE 'image_path'");
            if ($columnCheck->rowCount() == 0) {
                $pdo->exec("ALTER TABLE crime_tips ADD COLUMN image_path VARCHAR(255) NULL AFTER details");
            }
        }

        // Handle image upload if present
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = $_FILES['image'];
            
            // Validate image type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($image['type'], $allowedTypes)) {
                apiResponse::error("Invalid image type. Only JPEG, PNG, and GIF are allowed.", 400);
            }
            
            // Validate image size (max 5MB)
            $maxSize = 5 * 1024 * 1024;
            if ($image['size'] > $maxSize) {
                apiResponse::error("Image size exceeds 5MB limit.", 400);
            }
            
            // Create upload directory if it doesn't exist
            $uploadDir = __DIR__ . '/../uploads/tip_images/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
            $filename = uniqid('tip_', true) . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($image['tmp_name'], $filepath)) {
                apiResponse::error("Failed to upload image.", 500);
            }
            
            // Store relative path for database
            $image_path = 'uploads/tip_images/' . $filename;
        }

        // Insert the tip
        $query = "
            INSERT INTO crime_tips
            (crime_type, location, date_of_crime, details, image_path, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $crime_type,
            $location,
            $date_of_crime,
            $details,
            $image_path
        ]);

        $tipId = $pdo->lastInsertId();

        // Return success response.
        apiResponse::success([
            'id' => $tipId,
            'crime_type' => $crime_type,
            'location' => $location,
            'date_of_crime' => $date_of_crime,
            'details' => $details,
            'image_path' => $image_path,
            'status' => 'pending'
        ], "Crime tip submitted successfully");

    } elseif ($method === 'GET') {
        // Retrieve crime tips for external systems - no authentication required
        // Get query parameters for filtering (optional)
        $status = $_GET['status'] ?? null;
        $crime_type = $_GET['crime_type'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $date_from = $_GET['date_from'] ?? null;
        $date_to = $_GET['date_to'] ?? null;

        // Validate limit
        if ($limit > 100) {
            $limit = 100; // Max 100 records per request
        }
        if ($limit < 1) {
            $limit = 10;
        }

        // Build query
        $sql = "SELECT id, crime_type, location, date_of_crime, details, status, created_at, updated_at FROM crime_tips WHERE 1=1";
        $params = [];

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        if ($crime_type) {
            $sql .= " AND crime_type LIKE ?";
            $params[] = '%' . $crime_type . '%';
        }

        if ($date_from) {
            $sql .= " AND created_at >= ?";
            $params[] = $date_from;
        }

        if ($date_to) {
            $sql .= " AND created_at <= ?";
            $params[] = $date_to;
        }

        // Add ordering and pagination
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM crime_tips WHERE 1=1";
        $countParams = [];

        if ($status) {
            $countSql .= " AND status = ?";
            $countParams[] = $status;
        }

        if ($crime_type) {
            $countSql .= " AND crime_type LIKE ?";
            $countParams[] = '%' . $crime_type . '%';
        }

        if ($date_from) {
            $countSql .= " AND created_at >= ?";
            $countParams[] = $date_from;
        }

        if ($date_to) {
            $countSql .= " AND created_at <= ?";
            $countParams[] = $date_to;
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        apiResponse::success([
            'tips' => $tips,
            'pagination' => [
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total
            ]
        ], "Crime tips retrieved successfully");

    } elseif ($method === 'PUT' || $method === 'PATCH') {
        // Update tip status (for external systems) - no authentication required
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!is_array($data)) {
            apiResponse::error("Invalid JSON input.", 400);
        }

        $tip_id = $data['tip_id'] ?? null;
        $status = $data['status'] ?? null;

        if (!$tip_id || !$status) {
            apiResponse::error("Missing required fields: tip_id, status", 400);
        }

        $validStatuses = ['pending', 'reviewed', 'investigating', 'resolved', 'rejected'];
        if (!in_array($status, $validStatuses)) {
            apiResponse::error("Invalid status. Must be one of: " . implode(', ', $validStatuses), 400);
        }

        $stmt = $pdo->prepare("UPDATE crime_tips SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $tip_id]);

        if ($stmt->rowCount() === 0) {
            apiResponse::error("Tip not found", 404);
        }

        apiResponse::success([
            'tip_id' => $tip_id,
            'status' => $status
        ], "Tip status updated successfully");

    } else {
        apiResponse::error("Invalid request method. Use GET, POST, PUT, or PATCH.", 405);
    }

} catch (PDOException $e) {
    error_log("Crime Tips DB Error: " . $e->getMessage());
    error_log("Crime Tips DB Error Code: " . $e->getCode());
    error_log("Crime Tips DB Error Trace: " . $e->getTraceAsString());
    apiResponse::error("Unable to submit the crime tip.", 500);
} catch (Exception $e) {
    error_log("Crime Tips Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred while processing the crime tip.", 500);
}
