<?php
// Global USERS header block (sidebar + top header + sidebar toggle)

include __DIR__ . '/sidebar.php';
include __DIR__ . '/admin-style-header.php';
?>

<button class="sidebar-toggle-btn" aria-label="Toggle menu" onclick="window.sidebarToggle()" data-no-translate>
    <i class="fas fa-bars"></i>
</button>

<?php if (!empty($_SESSION['user_id'])): ?>
<script src="js/alert-listener.js?v=<?= filemtime(__DIR__ . '/../js/alert-listener.js') ?>" defer></script>
<?php endif; ?>
