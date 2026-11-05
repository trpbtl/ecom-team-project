<?php /** One product card. Expects $p (a product row). */ ?>
<article class="card overflow-hidden flex flex-col">
    <a href="<?= url('views/single_product.php?pro_id=' . (int) $p['product_id']) ?>" class="block aspect-[4/3] bg-sand">
        <img src="<?= product_image_url($p['image']) ?>" alt="<?= e($p['name']) ?>" class="w-full h-full object-cover" loading="lazy">
    </a>
    <div class="p-4 flex flex-col gap-2 flex-1">
        <p class="label"><?= e($p['category_name']) ?></p>
        <h3 class="font-display font-bold text-lg leading-tight">
            <a href="<?= url('views/single_product.php?pro_id=' . (int) $p['product_id']) ?>" class="hover:text-packe-600"><?= e($p['name']) ?></a>
        </h3>
        <p class="tabular-nums text-ink/90">
            <?php if ($p['is_customizable']): ?>From <?= money($p['base_price']) ?> per box<?php else: ?><?= money($p['base_price']) ?><?php endif; ?>
        </p>
        <div class="mt-auto pt-2 flex gap-2">
            <?php if ($p['is_customizable']): ?>
                <a href="<?= url('views/customize.php?product_id=' . (int) $p['product_id']) ?>" class="btn btn-primary flex-1">Design in 3D</a>
            <?php else: ?>
                <form action="<?= url('actions/add_to_cart_action.php') ?>" method="POST" class="flex-1">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $p['product_id'] ?>">
                    <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
                    <button class="btn btn-primary w-full">Add to cart</button>
                </form>
            <?php endif; ?>
            <a href="<?= url('views/single_product.php?pro_id=' . (int) $p['product_id']) ?>" class="btn btn-ghost">Details</a>
        </div>
    </div>
</article>
