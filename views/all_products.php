<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$pc = new ProductController();
$category = isset($_GET['cat']) ? $pc->getCategoryById($_GET['cat']) : null;
$products = $category ? $pc->getProductsByCategory($category['category_id']) : $pc->getAllProducts();
$pageTitle = $category ? $category['category_name'] : 'All products';
require __DIR__ . '/layout/header.php';
?>

<div class="flex flex-col md:flex-row gap-8">
    <?php require __DIR__ . '/layout/sidebar.php'; ?>
    <section class="flex-1 min-w-0">
        <h1 class="font-display font-extrabold text-3xl mb-1"><?= e($pageTitle) ?></h1>
        <p class="text-muted mb-6 tabular-nums"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?></p>
        <?php if (!$products): ?>
            <p class="card p-8 text-center text-muted">No products found in this category.</p>
        <?php else: ?>
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($products as $p) include __DIR__ . '/partials/product_card.php'; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
