<?php
/** Categories sidebar. Data comes from ProductController (no SQL here). */
require_once __DIR__ . '/../../controllers/ProductController.php';
$__categories = (new ProductController())->getAllCategories();
$__activeCat = (int) ($_GET['cat'] ?? 0);
?>
<aside class="w-full md:w-56 shrink-0">
    <h2 class="label mb-3">Categories</h2>
    <ul class="flex md:flex-col flex-wrap gap-2">
        <li>
            <a href="<?= url('views/all_products.php') ?>"
               class="chip <?= $__activeCat === 0 ? 'chip-active' : '' ?>">All products</a>
        </li>
        <?php foreach ($__categories as $c): ?>
            <li>
                <a href="<?= url('views/all_products.php?cat=' . (int) $c['category_id']) ?>"
                   class="chip <?= $__activeCat === (int) $c['category_id'] ? 'chip-active' : '' ?>">
                    <?= e($c['category_name']) ?> <span class="text-muted tabular-nums">(<?= (int) $c['product_count'] ?>)</span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <a href="<?= url('views/customize.php') ?>" class="hidden md:block mt-6 rounded-xl bg-ink text-cream p-4 hover:bg-black">
        <span class="font-display font-bold text-lg">Design a box in 3D</span>
        <span class="block text-sm text-cream/70 mt-1">Pick a size, upload your logo, see it fold.</span>
    </a>
</aside>
