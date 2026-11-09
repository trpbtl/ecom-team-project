<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$pc = new ProductController();
$products = $pc->getFeaturedProducts(4);
$categories = array_filter($pc->getAllCategories(), fn($c) => (int) $c['product_count'] > 0);
// Hero showcase: the name and starting price of each packaging type it cycles through (js/hero3d.js)
$showcase = [];
foreach (['mailer', 'foodbox', 'pouch', 'carton', 'catalog', 'sticker'] as $type) {
    $showcase[$type] = ['label' => 'Custom ' . strtolower(DesignTypes::TYPES[$type]['label']), 'from' => money(DesignTypes::fromPrice($type))];
}
$pageTitle = 'Custom packaging for Ghanaian businesses';
$extraHead = DesignTypes::fontsLink();
$extraScripts = three_scripts('js/hero3d.js');
require __DIR__ . '/layout/header.php';
?>

<!-- Hero: one line of promise, and live 3D packaging that shows it -->
<section class="grid lg:grid-cols-[1fr_1.15fr] gap-8 lg:gap-4 items-center -mt-2">
    <div class="flex flex-col gap-6 order-2 lg:order-1">
        <p class="inline-flex self-start items-center gap-2 rounded-full bg-white border border-line px-3 py-1.5 text-sm">
            <span class="w-2 h-2 rounded-full bg-packe-500"></span> Custom packaging, made in Ghana
        </p>
        <h1 class="font-display font-extrabold text-5xl md:text-7xl leading-[0.95] tracking-tight">
            Packaging that<br>looks like <span class="text-packe-600">your brand.</span>
        </h1>
        <p class="text-lg text-muted max-w-md">Design it in 3D, see the price instantly, and we deliver.</p>
        <div class="flex flex-wrap gap-3">
            <a href="<?= url('views/customize.php') ?>" class="btn btn-primary !px-6 !py-3.5 text-base">Design your packaging</a>
            <a href="<?= url('views/all_products.php') ?>" class="btn btn-ghost !px-6 !py-3.5 text-base">Browse packaging</a>
        </div>
        <ul class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-ink/80 pt-2">
            <li class="flex items-center gap-2"><svg class="w-5 h-5 text-packe-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2 3 7v10l9 5 9-5V7z"/><path d="M3 7l9 5 9-5M12 12v10"/></svg>Live 3D preview</li>
            <li class="flex items-center gap-2"><svg class="w-5 h-5 text-packe-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 12V8H6a2 2 0 0 1 0-4h12v4"/><path d="M4 6v12a2 2 0 0 0 2 2h14v-4"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg>Instant pricing</li>
            <li class="flex items-center gap-2"><svg class="w-5 h-5 text-packe-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 3h15v13H1zM16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>Express delivery</li>
        </ul>
    </div>

    <div class="relative order-1 lg:order-2">
        <div class="relative rounded-[2rem] overflow-hidden aspect-[5/4] bg-[radial-gradient(ellipse_at_50%_45%,#fff7ef_0%,#f6e3d2_45%,#ecd9c3_100%)]">
            <div class="absolute -right-16 -top-16 w-72 h-72 rounded-full bg-packe-500/15 blur-3xl" aria-hidden="true"></div>
            <canvas id="hero-gl" class="absolute inset-0 w-full h-full cursor-grab" data-fallback="<?= url('images/catalog/mailer-box.svg') ?>"
                    data-showcase='<?= e(json_encode($showcase)) ?>'
                    aria-label="Custom packaging turning in 3D: mailer box, food box, pouch, carton, catalogue and sticker"></canvas>
            <!-- floating details -->
            <div class="absolute left-4 top-4 md:left-6 md:top-6 rounded-2xl bg-white/90 backdrop-blur px-4 py-3 shadow-sm">
                <p class="text-xs text-muted" id="hero-type">Custom mailer box</p>
                <p class="font-display font-extrabold text-xl tabular-nums" id="hero-price">from <?= e($showcase['mailer']['from']) ?></p>
            </div>
            <div id="hero-look" class="absolute right-4 bottom-4 md:right-6 md:bottom-6 flex items-center gap-2 rounded-full bg-white/90 backdrop-blur pl-2 pr-4 py-2 shadow-sm text-sm">
                <span data-swatch class="w-6 h-6 rounded-full border border-black/10" style="background:#d14615"></span>
                <span data-name>Kraft · Packe orange</span>
            </div>
            <p class="absolute left-4 bottom-5 md:left-6 text-xs text-ink/50 hidden sm:block">Drag to turn it</p>
        </div>
    </div>
</section>

<!-- Shop by type: pictures first -->
<section class="mt-20">
    <div class="flex items-end justify-between gap-4 mb-6">
        <h2 class="font-display font-extrabold text-3xl md:text-4xl">Shop by type</h2>
        <a href="<?= url('views/all_products.php') ?>" class="text-packe-600 font-semibold hover:underline">All products →</a>
    </div>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-<?= min(4, max(1, count($categories))) ?>">
        <?php foreach ($categories as $c): ?>
            <a href="<?= url('views/all_products.php?cat=' . (int) $c['category_id']) ?>" class="group card overflow-hidden flex flex-col hover:border-ink transition-colors">
                <div class="aspect-[4/3] bg-sand overflow-hidden">
                    <img src="<?= product_image_url($c['cover_image']) ?>" alt="" class="w-full h-full object-cover group-hover:scale-[1.04] transition-transform duration-500" loading="lazy">
                </div>
                <div class="p-5 flex items-center justify-between gap-3">
                    <span class="font-display font-bold text-xl"><?= e($c['category_name']) ?></span>
                    <span class="text-sm text-muted tabular-nums"><?= (int) $c['product_count'] ?> items</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- How it works: three steps, few words -->
<section class="mt-20 rounded-[2rem] bg-ink text-cream px-6 py-12 md:px-12">
    <h2 class="font-display font-extrabold text-3xl md:text-4xl">From idea to doorstep</h2>
    <ol class="mt-8 grid gap-8 md:grid-cols-3">
        <?php foreach ([
            ['Design', 'Size, board, colour and your logo, live in 3D.'],
            ['Pay', 'Mobile money or card with Paystack.'],
            ['Receive', 'Standard or express, by our own riders.'],
        ] as $i => [$title, $line]): ?>
            <li class="flex flex-col gap-3">
                <span class="font-display font-extrabold text-6xl text-[#f26a3a] leading-none tabular-nums">0<?= $i + 1 ?></span>
                <span class="font-display font-bold text-2xl"><?= $title ?></span>
                <span class="text-cream/65"><?= $line ?></span>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<!-- Popular products -->
<?php if ($products): ?>
<section class="mt-20">
    <div class="flex items-end justify-between gap-4 mb-6">
        <h2 class="font-display font-extrabold text-3xl md:text-4xl">Popular right now</h2>
        <a href="<?= url('views/all_products.php') ?>" class="text-packe-600 font-semibold hover:underline">See all →</a>
    </div>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <?php foreach ($products as $p) include __DIR__ . '/partials/product_card.php'; ?>
    </div>
</section>
<?php endif; ?>

<!-- Closing call to action -->
<section class="mt-20 mb-4 grid md:grid-cols-[1fr_auto] items-center gap-6 rounded-[2rem] bg-packe-600 text-white px-6 py-10 md:px-12">
    <div>
        <h2 class="font-display font-extrabold text-3xl md:text-4xl">Your box. Your brand.</h2>
        <p class="text-white/80 mt-2">See it in 3D before you pay a pesewa.</p>
    </div>
    <a href="<?= url('views/customize.php') ?>" class="btn bg-white text-packe-700 hover:bg-cream !px-6 !py-3.5 text-base">Start designing</a>
</section>

<?php require __DIR__ . '/layout/footer.php'; ?>
