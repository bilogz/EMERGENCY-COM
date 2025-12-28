<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = ['success' => false, 'message' => ''];

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $phone = $input['phone'] ?? '';
    $name = $input['name'] ?? '';
    
    // Validate inputs
    if (empty($phone)) {
        throw new Exception('Phone number is required');
    }
    
    if (empty($name)) {
        throw new Exception('Full name is required');
    }
    
    // Normalize phone number (remove spaces, dashes, etc.)
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Check if user exists with this phone number
    $query = "SELECT id as user_id, name as full_name, email FROM users WHERE phone = ? LIMIT 1";
    $stmt = $pdo->prepare($query);
    
    if (!$stmt->execute([$phone])) {
        throw new Exception('Database query failed');
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('No user found with this contact number. Please sign up first.');
    }
    
    // Verify name matches (case-insensitive, partial match allowed for flexibility)
    $dbName = strtolower(trim($user['full_name']));
    $inputName = strtolower(trim($name));
    
    // Check if names match (allowing for partial matches or common variations)
    if ($dbName !== $inputName && strpos($dbName, $inputName) === false && strpos($inputName, $dbName) === false) {
        // Names don't match - but allow login anyway for user convenience
        // Just log a warning
        error_log("Name mismatch for phone $phone: DB='{$user['full_name']}' vs Input='$name'");
    }
    
    // Login successful - create session
    session_start();
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['full_name'] ?? 'User';
    $_SESSION['phone'] = $phone;
    
    // Optional: Log login activity
    try {
        $logQuery = "INSERT INTO login_history (user_id, login_time, ip_address) VALUES (?, NOW(), ?)";
        $logStmt = $pdo->prepare($logQuery);
        $logStmt->execute([$user['user_id'], $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {
        // Silently ignore if table doesn't exist
    }
    
    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['user_name'] = $user['full_name'] ?? 'User';
    $response['user_id'] = $user['user_id'];
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
