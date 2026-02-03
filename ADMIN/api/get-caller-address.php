<?php
/**
 * Get Caller Address API
 * Fetches user address from database by user_id or phone
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $userId = $_GET['user_id'] ?? null;
    $phone = $_GET['phone'] ?? null;
    
    if (!$userId && !$phone) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'user_id or phone is required']);
        exit;
    }
    
    // Build query based on available parameter
    if ($userId) {
        $stmt = $pdo->prepare("
            SELECT 
                address,
                house_number,
                street,
                barangay,
                district
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                address,
                house_number,
                street,
                barangay,
                district
            FROM users
            WHERE phone = ?
        ");
        $stmt->execute([$phone]);
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Build address string
    $address = $user['address'];
    
    // If no address field, build from components
    if (empty($address)) {
        $parts = [];
        if (!empty($user['house_number'])) $parts[] = $user['house_number'];
        if (!empty($user['street'])) $parts[] = $user['street'];
        if (!empty($user['barangay'])) $parts[] = $user['barangay'];
        if (!empty($user['district'])) $parts[] = $user['district'];
        
        if (!empty($parts)) {
            $address = implode(', ', $parts);
            // Add Quezon City if barangay exists
            if (!empty($user['barangay'])) {
                $address .= ', Quezon City';
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'address' => $address ?: null,
        'components' => [
            'house_number' => $user['house_number'],
            'street' => $user['street'],
            'barangay' => $user['barangay'],
            'district' => $user['district']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Get caller address error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
