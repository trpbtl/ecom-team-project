<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$p = (new ProductController())->getProductById($_GET['pro_id'] ?? null);
if (!$p) {
    flash('error', 'That product was not found.');
    redirect('views/all_products.php');
}
$pageTitle = $p['name'];
require __DIR__ . '/layout/header.php';
?>

<nav class="text-sm text-muted mb-6" aria-label="Breadcrumb">
    <a href="<?= url('views/all_products.php') ?>" class="hover:underline">Shop</a> /
    <a href="<?= url('views/all_products.php?cat=' . (int) $p['category_id']) ?>" class="hover:underline"><?= e($p['category_name']) ?></a> /
    <span class="text-ink"><?= e($p['name']) ?></span>
</nav>

<div class="grid md:grid-cols-2 gap-10">
    <div class="card overflow-hidden aspect-[4/3]">
        <img src="<?= product_image_url($p['image']) ?>" alt="<?= e($p['name']) ?>" class="w-full h-full object-cover">
    </div>
    <div class="flex flex-col gap-4">
        <p class="label"><?= e($p['category_name']) ?></p>
        <h1 class="font-display font-extrabold text-4xl leading-tight"><?= e($p['name']) ?></h1>
        <p class="text-2xl tabular-nums"><?= $p['is_customizable'] ? 'From ' : '' ?><?= money($p['base_price']) ?><?= $p['is_customizable'] ? ' <span class="text-base text-muted">per box</span>' : '' ?></p>
        <?php if ($p['description']): ?><p class="text-ink/80 leading-relaxed max-w-prose"><?= nl2br(e($p['description'])) ?></p><?php endif; ?>
        <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
            <?php if ($p['material']): ?><dt class="text-muted">Material</dt><dd><?= e($p['material']) ?></dd><?php endif; ?>
            <?php if ($p['keywords']): ?><dt class="text-muted">Good for</dt><dd><?= e($p['keywords']) ?></dd><?php endif; ?>
        </dl>

        <?php if ($p['is_customizable']): ?>
            <a href="<?= url('views/customize.php?product_id=' . (int) $p['product_id']) ?>" class="btn btn-primary self-start mt-2">Design it in 3D</a>
            <p class="text-sm text-muted">Set the size, choose kraft or white, add your logo and see the price before you buy.</p>
        <?php else: ?>
            <form action="<?= url('actions/add_to_cart_action.php') ?>" method="POST" class="flex items-end gap-3 mt-2" data-validate="add-to-cart">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $p['product_id'] ?>">
                <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
                <label class="field w-28">
                    <span>Quantity</span>
                    <input type="number" name="quantity" value="1" min="1" max="10000" step="1" class="input tabular-nums" required>
                </label>
                <button class="btn btn-primary">Add to cart</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
