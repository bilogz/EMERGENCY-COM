<?php
/**
 * Clear Translation Cache
 * Use this to force fresh AI translations
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../../ADMIN/api/db_connect.php';

try {
    if ($pdo === null) {
        throw new Exception('Database connection failed');
    }
    
    $lang = $_GET['lang'] ?? null;
    $clearAll = $_GET['all'] ?? false;
    
    if ($clearAll === 'true' || $clearAll === '1') {
        // Clear ALL cached translations
        $stmt = $pdo->exec("DELETE FROM translation_cache");
        echo json_encode([
            'success' => true,
            'message' => 'All translation cache cleared',
            'deleted_count' => $stmt,
            'note' => 'Fresh AI translations will be generated on next request'
        ]);
    } elseif ($lang) {
        // Clear specific language
        $stmt = $pdo->prepare("DELETE FROM translation_cache WHERE target_lang = ?");
        $stmt->execute([$lang]);
        $count = $stmt->rowCount();
        
        echo json_encode([
            'success' => true,
            'message' => "Translation cache cleared for language: $lang",
            'deleted_count' => $count,
            'note' => 'Fresh AI translations will be generated on next request for this language'
        ]);
    } else {
        // Show current cache status
        $stmt = $pdo->query("
            SELECT 
                target_lang,
                COUNT(*) as count,
                translation_method,
                MAX(created_at) as last_cached
            FROM translation_cache
            GROUP BY target_lang, translation_method
            ORDER BY target_lang
        ");
        $cacheStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Translation cache status',
            'cache_entries' => $cacheStatus,
            'usage' => [
                'clear_specific' => 'Add ?lang=zh to clear Chinese cache',
                'clear_all' => 'Add ?all=true to clear ALL cached translations',
                'examples' => [
                    '?lang=zh' => 'Clear Chinese translations',
                    '?lang=hi' => 'Clear Hindi translations',
                    '?lang=ar' => 'Clear Arabic translations',
                    '?all=true' => 'Clear ALL translations'
                ]
            ]
        ], JSON_PRETTY_PRINT);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

