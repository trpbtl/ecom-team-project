<?php
/** Admin: load or remove the presentation demo data (Dashboard → Demo data). */
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/DemoDataController.php';

require_admin();
require_post('views/admin/dashboard.php');

$dc = new DemoDataController();
if (($_POST['op'] ?? '') === 'remove') {
    $dc->remove();
    flash('success', 'Demo data removed. Real orders and accounts were not touched.');
} else {
    $r = $dc->load();
    flash($r['success'] ? 'success' : 'error', $r['success']
        ? "Demo data loaded: {$r['orders']} orders from {$r['customers']} businesses, " . money($r['revenue']) . ' in sales.'
        : $r['error']);
}
redirect('views/admin/dashboard.php');
