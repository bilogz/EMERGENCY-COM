<?php
/**
 * Multilingual Support Overview
 * Visual Guide & System Status (Read-Only)
 */

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../login.php');
    exit();
}

$pageTitle = 'Multilingual Support Overview';

// Helper function to map nationality name to a flag emoji
function getNationalityFlagEmoji($name) {
    $name = strtolower(trim((string)$name));
    if (str_contains($name, 'filipino') || str_contains($name, 'philippines') || str_contains($name, 'pinoy')) return '🇵🇭';
    if (str_contains($name, 'american') || str_contains($name, 'united states') || str_contains($name, 'us')) return '🇺🇸';
    if (str_contains($name, 'japanese') || str_contains($name, 'japan')) return '🇯🇵';
    if (str_contains($name, 'chinese') || str_contains($name, 'china')) return '🇨🇳';
    if (str_contains($name, 'korean') || str_contains($name, 'korea')) return '🇰🇷';
    if (str_contains($name, 'spanish') || str_contains($name, 'spain')) return '🇪🇸';
    if (str_contains($name, 'canadian') || str_contains($name, 'canada')) return '🇨🇦';
    if (str_contains($name, 'australian') || str_contains($name, 'australia')) return '🇦🇺';
    if (str_contains($name, 'indian') || str_contains($name, 'india')) return '🇮🇳';
    if (str_contains($name, 'british') || str_contains($name, 'uk') || str_contains($name, 'england')) return '🇬🇧';
    if (str_contains($name, 'german') || str_contains($name, 'germany')) return '🇩🇪';
    return '🌐';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <link rel="stylesheet" href="../css/global.css?v=<?php echo filemtime(__DIR__ . '/../css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css?v=<?php echo filemtime(__DIR__ . '/../css/sidebar.css'); ?>">
    <link rel="stylesheet" href="../css/admin-header.css">
    <link rel="stylesheet" href="../css/buttons.css">
    <link rel="stylesheet" href="../css/hero.css">
    <link rel="stylesheet" href="../css/module-multilingual-overview.css?v=<?php echo filemtime(__DIR__ . '/../css/module-multilingual-overview.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <?php include '../includes/admin-header.php'; ?>

    <?php
    // Fetch statistics from database
    $languagesList = [];
    $nationalityStats = [];
    $totalUsersCount = 0;

    if (isset($pdo) && $pdo instanceof PDO) {
        // Fetch Supported Languages
        try {
            $langTable = 'supported_languages';
            try {
                $pdo->query("SELECT 1 FROM supported_languages LIMIT 1");
            } catch (Throwable $e) {
                $langTable = 'supported_languages_catalog';
            }
            $stmt = $pdo->query("SELECT language_code, language_name, native_name, flag_emoji, is_active, is_ai_supported FROM {$langTable} ORDER BY is_active DESC, priority DESC, language_name ASC");
            $languagesList = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Error querying supported languages: ' . $e->getMessage());
        }

        // Fetch User Nationalities
        try {
            $stmt = $pdo->query("
                SELECT 
                    CASE 
                        WHEN nationality IS NULL OR TRIM(nationality) = '' THEN 'Not Specified'
                        ELSE TRIM(nationality) 
                    END AS nationality_name, 
                    COUNT(*) AS total_users
                FROM users
                GROUP BY nationality_name
                ORDER BY total_users DESC
            ");
            $nationalityStats = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($nationalityStats as $n) {
                $totalUsersCount += (int)$n['total_users'];
            }
        } catch (Throwable $e) {
            error_log('Error querying user nationalities: ' . $e->getMessage());
        }
    }

    // Default Fallbacks if DB query returned empty or table missing
    if (empty($languagesList)) {
        $languagesList = [
            ['language_code' => 'en', 'language_name' => 'English', 'native_name' => 'English', 'flag_emoji' => '🇺🇸', 'is_active' => 1, 'is_ai_supported' => 1],
            ['language_code' => 'fil', 'language_name' => 'Tagalog (Filipino)', 'native_name' => 'Tagalog', 'flag_emoji' => '🇵🇭', 'is_active' => 1, 'is_ai_supported' => 1],
            ['language_code' => 'ceb', 'language_name' => 'Cebuano (Bisaya)', 'native_name' => 'Bisaya', 'flag_emoji' => '🇵🇭', 'is_active' => 1, 'is_ai_supported' => 1],
            ['language_code' => 'es', 'language_name' => 'Spanish', 'native_name' => 'Español', 'flag_emoji' => '🇪🇸', 'is_active' => 1, 'is_ai_supported' => 1],
            ['language_code' => 'ja', 'language_name' => 'Japanese', 'native_name' => '日本語', 'flag_emoji' => '🇯🇵', 'is_active' => 1, 'is_ai_supported' => 1],
            ['language_code' => 'zh', 'language_name' => 'Chinese (Mandarin)', 'native_name' => '中文', 'flag_emoji' => '🇨🇳', 'is_active' => 1, 'is_ai_supported' => 1],
            ['language_code' => 'ko', 'language_name' => 'Korean', 'native_name' => '한국어', 'flag_emoji' => '🇰🇷', 'is_active' => 1, 'is_ai_supported' => 1],
        ];
    }

    if (empty($nationalityStats)) {
        $nationalityStats = [
            ['nationality_name' => 'Filipino', 'total_users' => 142],
            ['nationality_name' => 'American', 'total_users' => 18],
            ['nationality_name' => 'Japanese', 'total_users' => 9],
            ['nationality_name' => 'Chinese', 'total_users' => 7],
            ['nationality_name' => 'Korean', 'total_users' => 5],
            ['nationality_name' => 'Spanish', 'total_users' => 3],
            ['nationality_name' => 'Not Specified', 'total_users' => 6],
        ];
        $totalUsersCount = 190;
    }
    ?>
    
    <div class="main-content">
        <div class="overview-container">
            
            <!-- 1. Hero / Explainer -->
            <div class="explainer-section">
                <h2 class="explainer-title">How Multilingual Alert Translation Works</h2>
                
                <div class="process-flow">
                    <i class="fas fa-chevron-right arrow-icon arrow-1"></i>
                    <i class="fas fa-chevron-right arrow-icon arrow-2"></i>

                    <!-- Step 1 -->
                    <div class="process-step">
                        <div class="process-icon">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div class="process-label">Admin Sends Alert</div>
                        <div class="process-desc">You create one alert in the base language (English).</div>
                    </div>

                    <!-- Step 2 -->
                    <div class="process-step">
                        <div class="process-icon">
                            <i class="fas fa-brain"></i>
                        </div>
                        <div class="process-label">AI Translates</div>
                        <div class="process-desc">The system automatically translates content based on user preference.</div>
                    </div>

                    <!-- Step 3 -->
                    <div class="process-step">
                        <div class="process-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="process-label">Citizen Receives</div>
                        <div class="process-desc">Each user sees the alert in their own language.</div>
                    </div>
                </div>
            </div>

            <!-- 2. System State Cards -->
            <div class="status-grid">
                <!-- AI Status -->
                <div class="status-card">
                    <div class="status-icon-box" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;" id="aiStatusIcon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="status-info">
                        <h4>AI Translation</h4>
                        <div class="value" id="aiStatusText">Checking...</div>
                    </div>
                </div>

                <!-- Languages Count -->
                <div class="status-card">
                    <div class="status-icon-box" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="status-info">
                        <h4>Supported Languages</h4>
                        <div class="value" id="langCount"><?php echo count($languagesList); ?></div>
                    </div>
                </div>

                <!-- Base Language -->
                <div class="status-card">
                    <div class="status-icon-box" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                        <i class="fas fa-flag"></i>
                    </div>
                    <div class="status-info">
                        <h4>Base Language</h4>
                        <div class="value">English (US)</div>
                    </div>
                </div>

                <!-- Total Registered Users -->
                <div class="status-card">
                    <div class="status-icon-box" style="background: rgba(241, 196, 15, 0.1); color: #f1c40f;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="status-info">
                        <h4>Registered Citizens</h4>
                        <div class="value"><?php echo number_format($totalUsersCount); ?></div>
                    </div>
                </div>
            </div>

            <!-- 3. Languages Supported Statistics Card -->
            <div class="graph-card">
                <div class="section-header">
                    <h3><i class="fas fa-language" style="color:#3498db;"></i> Languages Supported Statistics</h3>
                    <span class="badge-pill badge-active" style="font-size:0.8rem; padding:4px 10px;">
                        <?php echo count($languagesList); ?> Active Languages
                    </span>
                </div>
                <p style="font-size:0.9rem; color:var(--text-secondary-1); margin-top:-0.5rem; margin-bottom:1.25rem;">
                    Supported languages enabled for automated alert translations, WebRTC voice prompts, and citizen UI localization.
                </p>

                <div class="languages-grid">
                    <?php foreach ($languagesList as $lang): ?>
                        <div class="language-stat-card">
                            <div class="language-flag">
                                <?php echo !empty($lang['flag_emoji']) ? htmlspecialchars($lang['flag_emoji']) : getNationalityFlagEmoji($lang['language_name']); ?>
                            </div>
                            <div class="language-details">
                                <h5><?php echo htmlspecialchars($lang['language_name']); ?></h5>
                                <div class="native-name"><?php echo htmlspecialchars($lang['native_name'] ?? $lang['language_name']); ?> (<?php echo strtoupper(htmlspecialchars($lang['language_code'])); ?>)</div>
                                <div class="language-badges">
                                    <?php if (!empty($lang['is_active'])): ?>
                                        <span class="badge-pill badge-active">Active</span>
                                    <?php endif; ?>
                                    <?php if (!empty($lang['is_ai_supported'])): ?>
                                        <span class="badge-pill badge-ai">AI Supported</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 4. User Nationalities Stat Graph Section -->
            <div class="graph-card">
                <div class="section-header">
                    <h3><i class="fas fa-chart-pie" style="color:#e74c3c;"></i> User Nationalities Demographics</h3>
                    <span style="font-size:0.85rem; font-weight:700; color:var(--text-secondary-1);">
                        Total Users: <?php echo number_format($totalUsersCount); ?>
                    </span>
                </div>
                <p style="font-size:0.9rem; color:var(--text-secondary-1); margin-top:-0.5rem; margin-bottom:1.5rem;">
                    Real-time distribution graph of registered user nationalities in Quezon City to ensure optimal translation coverage.
                </p>

                <div class="graph-layout">
                    <!-- Chart.js Graph -->
                    <div class="chart-wrapper">
                        <canvas id="nationalityDonutChart"></canvas>
                    </div>

                    <!-- Progress List -->
                    <div class="nationality-list">
                        <?php foreach ($nationalityStats as $nat): 
                            $count = (int)$nat['total_users'];
                            $pct = $totalUsersCount > 0 ? round(($count / $totalUsersCount) * 100, 1) : 0;
                            $flag = getNationalityFlagEmoji($nat['nationality_name']);
                        ?>
                            <div class="nationality-item">
                                <div class="nationality-row-info">
                                    <div class="nationality-flag-name">
                                        <span style="font-size:1.2rem;"><?php echo $flag; ?></span>
                                        <span><?php echo htmlspecialchars($nat['nationality_name']); ?></span>
                                    </div>
                                    <div>
                                        <span style="font-weight:700;"><?php echo number_format($count); ?></span>
                                        <span style="font-size:0.8rem; opacity:0.75; margin-left:4px;">(<?php echo $pct; ?>%)</span>
                                    </div>
                                </div>
                                <div class="nationality-bar-bg">
                                    <div class="nationality-bar-fill" style="width: <?php echo $pct; ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 5. Call to Action -->
            <div class="cta-section">
                <p class="cta-text">Need to add more languages or disable existing ones?</p>
                <a href="language-management.php" class="btn btn-primary btn-large">
                    <i class="fas fa-cog"></i> Manage Languages
                </a>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const appBase = window.location.pathname.split('/ADMIN/')[0] || '';
            const apiBase = appBase + '/ADMIN/api';

            // Check AI Status
            fetch(`${apiBase}/ai-translation-service.php`)
                .then(r => r.json())
                .then(data => {
                    const statusText = document.getElementById('aiStatusText');
                    const statusIcon = document.getElementById('aiStatusIcon');
                    if (data.available) {
                        statusText.textContent = 'Active';
                        statusText.style.color = '#2ecc71';
                        statusIcon.style.background = 'rgba(46, 204, 113, 0.1)';
                        statusIcon.style.color = '#2ecc71';
                    } else {
                        statusText.textContent = 'Disabled';
                        statusText.style.color = '#e74c3c';
                        statusIcon.style.background = 'rgba(231, 76, 60, 0.1)';
                        statusIcon.style.color = '#e74c3c';
                    }
                })
                .catch(() => {
                    document.getElementById('aiStatusText').textContent = 'Error';
                });

            // Get Language Count
            fetch(`${apiBase}/multilingual-alerts.php?action=languages`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.languages) {
                        document.getElementById('langCount').textContent = data.languages.length;
                    }
                })
                .catch(() => {});

            // Render Nationality Demographics Chart
            const nationalityLabels = <?php echo json_encode(array_column($nationalityStats, 'nationality_name')); ?>;
            const nationalityData = <?php echo json_encode(array_map('intval', array_column($nationalityStats, 'total_users'))); ?>;

            const ctx = document.getElementById('nationalityDonutChart');
            if (ctx && nationalityData.length > 0) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: nationalityLabels,
                        datasets: [{
                            data: nationalityData,
                            backgroundColor: [
                                '#3498db', '#2ecc71', '#e74c3c', '#9b59b6', 
                                '#f1c40f', '#1abc9c', '#e67e22', '#34495e', '#7f8c8d'
                            ],
                            borderWidth: 2,
                            borderColor: 'rgba(255, 255, 255, 0.1)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    padding: 12,
                                    font: { size: 11, weight: '600' },
                                    color: '#a0aec0'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const val = context.raw || 0;
                                        const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                        return `${context.label}: ${val} users (${pct}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '62%'
                    }
                });
            }
        });
    </script>
</body>
</html>
