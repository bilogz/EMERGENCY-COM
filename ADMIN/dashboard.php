<?php
session_start();

// Step 1: Get JWT token from URL or session
$token = $_GET['token'] ?? $_SESSION['jwt_token'] ?? null;

if (!$token) {
    header('Location: https://login.alertaraqc.com');
    exit;
}

// Store in session for next page visit
$_SESSION['jwt_token'] = $token;

// Step 2: Validate token via API
$ch = curl_init('https://login.alertaraqc.com/api/auth/validate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
]);

$response = json_decode(curl_exec($ch), true);
curl_close($ch);

// Step 3: Check if validation passed
if (!$response['authenticated']) {
    header('Location: https://login.alertaraqc.com');
    exit;
}

// Step 4: Get user data from response
$user = $response['user'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
    <!-- Hide token from URL -->
    <script>
        if (window.location.search.includes('token=')) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>

    <h1>Welcome <?= $user['email'] ?></h1>
    <p>Role: <strong><?= $user['role'] ?></strong></p>
    <p>Department: <strong><?= $user['department_name'] ?></strong></p>
    <a href="https://login.alertaraqc.com/logout">Logout</a>
</body>
</html>