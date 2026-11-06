<?php
/** One product card. Expects $p (a product row). Plain "Add to cart" (no reload, js/cart.js) and, if it has a template, "Design it". */
require_once __DIR__ . '/../../controllers/DesignTypes.php';
$__t = DesignTypes::get($p['design_type'] ?? null);
?>
<article class="card overflow-hidden flex flex-col">
    <a href="<?= url('views/single_product.php?pro_id=' . (int) $p['product_id']) ?>" class="block aspect-[4/3] bg-sand">
        <img src="<?= product_image_url($p['image']) ?>" alt="<?= e($p['name']) ?>" class="w-full h-full object-cover" loading="lazy">
    </a>
    <div class="p-4 flex flex-col gap-2 flex-1">
        <p class="label"><?= e($p['category_name']) ?></p>
        <h3 class="font-display font-bold text-lg leading-tight">
            <a href="<?= url('views/single_product.php?pro_id=' . (int) $p['product_id']) ?>" class="hover:text-packe-600"><?= e($p['name']) ?></a>
        </h3>
        <p class="tabular-nums text-ink/90"><?= money($p['base_price']) ?></p>
        <?php if ($__t): ?>
            <p class="text-sm text-muted tabular-nums -mt-1">Custom from <?= money(DesignTypes::fromPrice($p['design_type'])) ?> per <?= e($__t['noun'][0]) ?></p>
        <?php endif; ?>
        <div class="mt-auto pt-2 flex gap-2">
            <form action="<?= url('actions/add_to_cart_action.php') ?>" method="POST" class="flex-1" data-ajax-cart
                  data-price="<?= e($p['base_price']) ?>" data-name="<?= e($p['name']) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $p['product_id'] ?>">
                <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
                <button class="btn <?= $__t ? 'btn-ghost' : 'btn-primary' ?> w-full">Add to cart</button>
            </form>
            <?php if ($__t): ?>
                <a href="<?= url('views/customize.php?product_id=' . (int) $p['product_id']) ?>" class="btn btn-primary flex-1">Design it</a>
            <?php endif; ?>
        </div>
    </div>
</article>
