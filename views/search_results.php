<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$q = trim((string) ($_GET['q'] ?? ''));
$products = (new ProductController())->searchProducts($q);
$pageTitle = 'Search results';
require __DIR__ . '/layout/header.php';
?>

<div class="flex flex-col md:flex-row gap-8">
    <?php require __DIR__ . '/layout/sidebar.php'; ?>
    <section class="flex-1 min-w-0">
        <h1 class="font-display font-extrabold text-3xl mb-1">Results for “<?= e($q) ?>”</h1>
        <?php if (mb_strlen($q) < 2): ?>
            <p class="card p-8 text-center text-muted mt-4">Type at least 2 characters to search.</p>
        <?php elseif (!$products): ?>
            <p class="card p-8 text-center text-muted mt-4">No products match “<?= e($q) ?>”. Try “box”, “label” or “brochure”.</p>
        <?php else: ?>
            <p class="text-muted mb-6 tabular-nums"><?= count($products) ?> found</p>
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($products as $p) include __DIR__ . '/partials/product_card.php'; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
