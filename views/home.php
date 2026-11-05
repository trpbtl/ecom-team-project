<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$products = (new ProductController())->getFeaturedProducts(8);
$pageTitle = 'Custom packaging for Ghanaian businesses';
require __DIR__ . '/layout/header.php';
?>

<section class="grid md:grid-cols-[1.1fr_1fr] gap-8 items-center rounded-3xl bg-ink text-cream p-8 md:p-12">
    <div class="flex flex-col gap-5">
        <p class="label !text-[#f6a27f]">Self-service packaging · Ghana</p>
        <h1 class="font-display font-extrabold text-4xl md:text-6xl leading-[1.02]">Design your box. See it in 3D. We deliver it.</h1>
        <p class="text-cream/75 text-lg max-w-xl">Pick a size, upload your logo and get an instant price, with no waiting for quotes. Standard or express delivery by our own team.</p>
        <div class="flex flex-wrap gap-3">
            <a href="<?= url('views/customize.php') ?>" class="btn btn-primary">Start designing</a>
            <a href="<?= url('views/all_products.php') ?>" class="btn btn-ghost !text-cream !border-cream/30 hover:!border-cream">View all products</a>
        </div>
    </div>
    <ol class="grid gap-3 text-sm">
        <?php foreach ([['Choose a size', 'Length, width and height in cm'], ['Pick board and colour', 'Kraft or white, any print colour'], ['Add your logo', 'It appears on the lid and front in 3D'], ['Pay and relax', 'Paystack checkout, then we deliver']] as $i => [$t, $d]): ?>
            <li class="flex gap-4 items-start rounded-2xl bg-white/5 p-4">
                <span class="font-display font-extrabold text-2xl text-[#f6a27f] tabular-nums w-6"><?= $i + 1 ?></span>
                <span><span class="block font-semibold text-base"><?= $t ?></span><span class="text-cream/65"><?= $d ?></span></span>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<section class="mt-12">
    <div class="flex items-end justify-between gap-4 mb-5">
        <h2 class="font-display font-extrabold text-3xl">Popular products</h2>
        <a href="<?= url('views/all_products.php') ?>" class="text-packe-600 font-semibold hover:underline">See all</a>
    </div>
    <?php if (!$products): ?>
        <p class="card p-8 text-center text-muted">No products yet. An admin can add them from the Products page.</p>
    <?php else: ?>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($products as $p) include __DIR__ . '/partials/product_card.php'; ?>
        </div>
    <?php endif; ?>
</section>

<section class="mt-12 grid sm:grid-cols-3 gap-5">
    <div class="card p-6"><p class="font-display font-bold text-xl">Standard delivery</p><p class="text-muted mt-1">Reliable and affordable for your regular orders.</p></div>
    <div class="card p-6"><p class="font-display font-bold text-xl">Express rush orders</p><p class="text-muted mt-1">Faster turnaround when you need packaging urgently.</p></div>
    <div class="card p-6"><p class="font-display font-bold text-xl">Track every order</p><p class="text-muted mt-1">Follow your order from paid to delivered in your account.</p></div>
</section>

<?php require __DIR__ . '/layout/footer.php'; ?>
