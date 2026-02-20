<?php
$assetBase = '../ADMIN/header/';
$confirmationCode = $_GET['code'] ?? '';
$status = 'unknown';
$message = '';
$deletionDate = '';

if ($confirmationCode) {
    require_once 'api/db_connect.php';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM data_deletion_requests WHERE confirmation_code = ?");
        $stmt->execute([$confirmationCode]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($request) {
            $status = $request['status'];
            $deletionDate = $request['completed_at'] ?? $request['requested_at'];
            
            if ($status === 'completed') {
                $message = 'Your data has been successfully deleted from our system.';
            } elseif ($status === 'pending') {
                $message = 'Your data deletion request is being processed.';
            } else {
                $message = 'Your data deletion request status: ' . ucfirst($status);
            }
        } else {
            $message = 'Invalid confirmation code. Please check your code and try again.';
        }
    } catch (PDOException $e) {
        $message = 'Unable to retrieve deletion status. Please contact support.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Deletion Status - LGU #4 Emergency Communication System</title>
    <link rel="icon" type="image/x-icon" href="<?= $assetBase ?>images/favicon.ico">
    <link rel="stylesheet" href="<?= $assetBase ?>css/global.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/buttons.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/global.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/sidebar.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/content.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/admin-header.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .status-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--card-bg, #ffffff);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
        }
        .status-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .status-icon.completed { color: #4caf50; }
        .status-icon.pending { color: #ff9800; }
        .status-icon.unknown { color: #f44336; }
        .status-code {
            background: rgba(76, 175, 80, 0.1);
            padding: 1rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            font-family: monospace;
            font-size: 1.2rem;
            word-break: break-all;
        }
        .status-message {
            font-size: 1.1rem;
            color: var(--text-color, #333);
            margin: 1rem 0;
            line-height: 1.6;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            padding: 0.75rem 1.5rem;
            background: #4caf50;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
        }
        .back-btn:hover {
            background: #2e7d32;
        }
        .check-form {
            margin-top: 2rem;
        }
        .check-form input {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 100%;
            max-width: 300px;
            margin-bottom: 1rem;
        }
        .check-form button {
            padding: 0.75rem 1.5rem;
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        .check-form button:hover {
            background: #2e7d32;
        }
        [data-theme="dark"] .status-container {
            background: #1a1a1a;
        }
        [data-theme="dark"] .status-message {
            color: #e0e0e0;
        }
    </style>
</head>
<body class="user-admin-header">
    <?php include 'includes/user-global-header.php'; ?>

    <main class="main-content">
        <div class="main-container">
            <div class="sub-container content-main">
                <div class="status-container">
                    <?php if ($confirmationCode && $status !== 'unknown'): ?>
                        <div class="status-icon <?= $status ?>">
                            <?php if ($status === 'completed'): ?>
                                <i class="fas fa-check-circle"></i>
                            <?php elseif ($status === 'pending'): ?>
                                <i class="fas fa-clock"></i>
                            <?php else: ?>
                                <i class="fas fa-info-circle"></i>
                            <?php endif; ?>
                        </div>
                        
                        <h1>Data Deletion Status</h1>
                        
                        <div class="status-code">
                            <?= htmlspecialchars($confirmationCode) ?>
                        </div>
                        
                        <div class="status-message">
                            <?= htmlspecialchars($message) ?>
                        </div>
                        
                        <?php if ($deletionDate): ?>
                            <p style="color: #666; font-size: 0.9rem;">
                                <i class="fas fa-calendar-alt"></i>
                                <?= $status === 'completed' ? 'Completed on: ' : 'Requested on: ' ?>
                                <?= date('F j, Y g:i A', strtotime($deletionDate)) ?>
                            </p>
                        <?php endif; ?>
                        
                    <?php elseif ($confirmationCode && $status === 'unknown'): ?>
                        <div class="status-icon unknown">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        
                        <h1>Invalid Confirmation Code</h1>
                        
                        <div class="status-message">
                            <?= htmlspecialchars($message) ?>
                        </div>
                        
                        <div class="check-form">
                            <p>Enter your confirmation code to check status:</p>
                            <form method="GET" action="">
                                <input type="text" name="code" placeholder="DEL-XXXXXXXX" required>
                                <br>
                                <button type="submit">Check Status</button>
                            </form>
                        </div>
                        
                    <?php else: ?>
                        <div class="status-icon">
                            <i class="fas fa-shield-alt" style="color: #4caf50;"></i>
                        </div>
                        
                        <h1>Data Deletion Status</h1>
                        
                        <div class="status-message">
                            Check the status of your data deletion request by entering your confirmation code below.
                        </div>
                        
                        <div class="check-form">
                            <form method="GET" action="">
                                <input type="text" name="code" placeholder="Enter confirmation code (e.g., DEL-XXXXXXXX)" required>
                                <br>
                                <button type="submit">Check Status</button>
                            </form>
                        </div>
                        
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #eee;">
                            <p style="color: #666; font-size: 0.9rem;">
                                <i class="fas fa-info-circle"></i>
                                If you requested data deletion from Facebook, you should have received a confirmation code.
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <a href="index.php" class="back-btn">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer-snippet.php'; ?>
</body>
</html>
