<?php
/**
 * Clear Translation Cache
 * Use this to force fresh AI translations
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once __DIR__ . '/../../USERS/api/translation-cache-store.php';

try {
    $lang = $_GET['lang'] ?? null;
    $clearAll = $_GET['all'] ?? false;

    if ($clearAll === 'true' || $clearAll === '1') {
        $result = translation_cache_clear(null, $pdo ?? null);
        $deletedByBackend = $result['deleted'];
        $totalDeleted = array_sum(array_map('intval', $deletedByBackend));

        echo json_encode([
            'success' => true,
            'message' => 'All translation cache cleared',
            'deleted_count' => $totalDeleted,
            'deleted_by_backend' => $deletedByBackend,
            'cache_driver' => $result['driver'],
            'cache_backends' => $result['backends'],
            'errors' => array_filter($result['errors']),
            'note' => 'Fresh translations will be generated on next request'
        ]);
    } elseif ($lang) {
        $result = translation_cache_clear($lang, $pdo ?? null);
        $deletedByBackend = $result['deleted'];
        $totalDeleted = array_sum(array_map('intval', $deletedByBackend));

        echo json_encode([
            'success' => true,
            'message' => "Translation cache cleared for language: $lang",
            'deleted_count' => $totalDeleted,
            'deleted_by_backend' => $deletedByBackend,
            'cache_driver' => $result['driver'],
            'cache_backends' => $result['backends'],
            'errors' => array_filter($result['errors']),
            'note' => 'Fresh translations will be generated on next request for this language'
        ]);
    } else {
        $status = translation_cache_status($pdo ?? null);

        echo json_encode([
            'success' => true,
            'message' => 'Translation cache status',
            'cache_entries' => $status['entries'],
            'cache_driver' => $status['driver'],
            'cache_backends' => $status['backends'],
            'errors' => array_filter($status['errors']),
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

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Cache clear failed: ' . $e->getMessage()
    ]);
}
?>

