<?php
/**
 * Debug Translation System
 * Shows what's in the cache and tests live translation
 */

header('Content-Type: text/html; charset=utf-8');
require_once '../../ADMIN/api/db_connect.php';
require_once 'ai-translation-config.php';

$lang = $_GET['lang'] ?? 'zh';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Translation Debug</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
        h1, h2 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
        pre { margin: 0; white-space: pre-wrap; word-wrap: break-word; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <h1>🔍 Translation System Debug</h1>
    
    <h2>1. Configuration Check</h2>
    <table>
        <tr><th>Setting</th><th>Value</th><th>Status</th></tr>
        <tr>
            <td>AI Provider</td>
            <td><?= AI_PROVIDER ?></td>
            <td class="success">✓</td>
        </tr>
        <tr>
            <td>API Key</td>
            <td><?= substr(AI_API_KEY, 0, 15) ?>...</td>
            <td class="<?= !empty(AI_API_KEY) && AI_API_KEY !== 'your-api-key-here' ? 'success' : 'error' ?>">
                <?= !empty(AI_API_KEY) && AI_API_KEY !== 'your-api-key-here' ? '✓ Configured' : '✗ Not configured' ?>
            </td>
        </tr>
        <tr>
            <td>Gemini Model</td>
            <td><?= defined('GEMINI_MODEL') ? GEMINI_MODEL : 'default' ?></td>
            <td class="success">✓</td>
        </tr>
        <tr>
            <td>Database</td>
            <td><?= $pdo ? 'Connected' : 'Not connected' ?></td>
            <td class="<?= $pdo ? 'success' : 'error' ?>"><?= $pdo ? '✓' : '✗' ?></td>
        </tr>
    </table>

    <h2>2. Cache Status for Language: <?= htmlspecialchars($lang) ?></h2>
    <?php
    if ($pdo) {
        try {
            // Check if table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'translation_cache'");
            $tableExists = $stmt->fetch();
            
            if (!$tableExists) {
                echo '<p class="error">❌ translation_cache table does not exist!</p>';
                echo '<p>Run: <code>ADMIN/api/setup-translation-cache.php</code></p>';
            } else {
                echo '<p class="success">✓ translation_cache table exists</p>';
                
                // Get cached translations for this language
                $stmt = $pdo->prepare("
                    SELECT cache_key, source_text, translated_text, translation_method, created_at 
                    FROM translation_cache 
                    WHERE target_lang = ?
                    ORDER BY created_at DESC
                    LIMIT 20
                ");
                $stmt->execute([$lang]);
                $cached = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($cached)) {
                    echo '<p class="warning">⚠️ No cached translations for ' . htmlspecialchars($lang) . '</p>';
                } else {
                    echo '<p>Found ' . count($cached) . ' cached translations:</p>';
                    echo '<table>';
                    echo '<tr><th>Source (English)</th><th>Translated</th><th>Same?</th><th>Method</th><th>Cached</th></tr>';
                    foreach ($cached as $row) {
                        $isSame = $row['source_text'] === $row['translated_text'];
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars(substr($row['source_text'], 0, 50)) . '...</td>';
                        echo '<td>' . htmlspecialchars(substr($row['translated_text'], 0, 50)) . '...</td>';
                        echo '<td class="' . ($isSame ? 'error' : 'success') . '">' . ($isSame ? '⚠️ YES!' : '✓ No') . '</td>';
                        echo '<td>' . htmlspecialchars($row['translation_method'] ?? 'unknown') . '</td>';
                        echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                    
                    // Count how many are same as source
                    $sameCount = 0;
                    foreach ($cached as $row) {
                        if ($row['source_text'] === $row['translated_text']) {
                            $sameCount++;
                        }
                    }
                    
                    if ($sameCount > 0) {
                        echo '<p class="error">⚠️ ' . $sameCount . ' translations are SAME as source (not translated!)</p>';
                        echo '<p>This means the AI translation failed and cached the English text.</p>';
                        echo '<p><strong>Solution:</strong> Clear the cache and try again.</p>';
                    }
                }
            }
        } catch (PDOException $e) {
            echo '<p class="error">Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
    ?>

    <h2>3. Live Translation Test</h2>
    <p>Testing translation to: <strong><?= htmlspecialchars($lang) ?></strong></p>
    <?php
    $testText = "Download Our Mobile App";
    echo '<p>Test text: <strong>' . htmlspecialchars($testText) . '</strong></p>';
    
    // Test live translation
    $translatedText = translateWithAI($testText, 'en', $lang);
    
    $isSame = $testText === $translatedText;
    echo '<p>Translated: <strong class="' . ($isSame ? 'error' : 'success') . '">' . htmlspecialchars($translatedText) . '</strong></p>';
    
    if ($isSame) {
        echo '<p class="error">❌ Translation FAILED - returned same text!</p>';
        echo '<p>This means the AI API is not working. Check:</p>';
        echo '<ul>';
        echo '<li>API key is valid</li>';
        echo '<li>API key has quota/credits</li>';
        echo '<li>Model name is correct</li>';
        echo '<li>Network can reach Google APIs</li>';
        echo '</ul>';
    } else {
        echo '<p class="success">✓ Translation is working!</p>';
    }
    ?>

    <h2>4. Actions</h2>
    <form method="get" style="display: inline;">
        <input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
        <select name="lang" onchange="this.form.submit()">
            <option value="zh" <?= $lang === 'zh' ? 'selected' : '' ?>>Chinese (zh)</option>
            <option value="hi" <?= $lang === 'hi' ? 'selected' : '' ?>>Hindi (hi)</option>
            <option value="es" <?= $lang === 'es' ? 'selected' : '' ?>>Spanish (es)</option>
            <option value="ar" <?= $lang === 'ar' ? 'selected' : '' ?>>Arabic (ar)</option>
            <option value="pt" <?= $lang === 'pt' ? 'selected' : '' ?>>Portuguese (pt)</option>
            <option value="ja" <?= $lang === 'ja' ? 'selected' : '' ?>>Japanese (ja)</option>
            <option value="ko" <?= $lang === 'ko' ? 'selected' : '' ?>>Korean (ko)</option>
        </select>
    </form>
    
    <a href="clear-translation-cache.php?lang=<?= htmlspecialchars($lang) ?>" class="btn btn-danger" 
       onclick="return confirm('Clear cache for <?= htmlspecialchars($lang) ?>?')">
        Clear Cache for <?= strtoupper($lang) ?>
    </a>
    
    <a href="clear-translation-cache.php?all=true" class="btn btn-danger" 
       onclick="return confirm('Clear ALL translation cache?')">
        Clear ALL Cache
    </a>
    
    <a href="<?= dirname($_SERVER['PHP_SELF']) ?>/clear-translation-cache.php" class="btn">
        View Cache Status
    </a>

    <h2>5. Quick Fix</h2>
    <div class="code">
        <p><strong>If translations are cached but not translated:</strong></p>
        <ol>
            <li>Click "Clear ALL Cache" above</li>
            <li>Go back to your homepage</li>
            <li>Select the language again</li>
            <li>Fresh AI translations will be generated</li>
        </ol>
    </div>

    <h2>6. Test Different Languages</h2>
    <p>Click to test:</p>
    <a href="?lang=zh" class="btn">Chinese</a>
    <a href="?lang=hi" class="btn">Hindi</a>
    <a href="?lang=es" class="btn">Spanish</a>
    <a href="?lang=ar" class="btn">Arabic</a>
    <a href="?lang=ja" class="btn">Japanese</a>
    <a href="?lang=ko" class="btn">Korean</a>
    <a href="?lang=pt" class="btn">Portuguese</a>
</body>
</html>

