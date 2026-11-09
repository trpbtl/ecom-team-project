<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../controllers/CustomizationController.php';

$pc = new ProductController();
$all = $pc->getAllProducts();
$designable = array_values(array_filter($all, fn($p) => DesignTypes::get($p['design_type']) !== null));
$product = isset($_GET['product_id']) ? $pc->getProductById($_GET['product_id']) : null;
if (!$product && $designable) {   // default to the first product that can be designed
    $product = $designable[0];
}
$type = $product['design_type'] ?? null;
$t = DesignTypes::get($type);
if (!$product || !$t) {
    flash('error', $product ? 'That product cannot be designed yet.' : 'There is no product to design yet.');
    redirect($product ? 'views/single_product.php?pro_id=' . (int) $product['product_id'] : 'views/all_products.php');
}

// Everything js/designer.js needs to draw and price this template (the server uses the same numbers)
$config = [
    'type' => $type, 'noun' => $t['noun'], 'folds' => $t['folds'], 'fields' => $t['fields'],
    'base' => $t['base'], 'rate' => $t['rate'], 'ink' => $t['ink'], 'brand' => $t['brand'],
    'qtys' => $t['qtys'], 'factors' => DesignTypes::FACTORS, 'fonts' => DesignTypes::FONTS,
];
$defaultQty = $t['qtys'][1];
$inks = ['' => 'No print colour', '#d14615' => 'Packe orange', '#1c1a18' => 'Black', '#1f4e79' => 'Navy', '#2f6b3a' => 'Green', '#e8b4b8' => 'Blush pink'];
$swatch = ['kraft' => '#c79c6b', 'white' => '#f4f2ee'];
$pageTitle = 'Design your ' . $product['name'];
$extraHead = DesignTypes::fontsLink();
$extraScripts = three_scripts('js/designer.js');
require __DIR__ . '/layout/header.php';
?>

<nav class="flex flex-wrap items-center gap-2 mb-5 text-sm" aria-label="Choose what to design">
    <span class="text-muted mr-1">Design:</span>
    <?php foreach ($designable as $d): $on = (int) $d['product_id'] === (int) $product['product_id']; ?>
        <a href="<?= url('views/customize.php?product_id=' . (int) $d['product_id']) ?>"
           class="chip !py-1 <?= $on ? 'chip-active' : '' ?>" <?= $on ? 'aria-current="page"' : '' ?>><?= e($d['name']) ?></a>
    <?php endforeach; ?>
</nav>

<div class="grid lg:grid-cols-[minmax(0,1fr)_400px] gap-6 items-start">
    <!-- 3D stage -->
    <section class="lg:sticky lg:top-24 rounded-2xl border border-line overflow-hidden relative h-[60vh] lg:h-[calc(100vh-8rem)] min-h-[420px] bg-[radial-gradient(ellipse_at_50%_40%,#ffffff_0%,#e9e3d9_100%)]" aria-label="Preview">
        <canvas id="gl" class="absolute inset-0 w-full h-full touch-none cursor-grab" aria-label="3D preview of your <?= e($t['noun'][0]) ?>"></canvas>
        <div id="dieline" class="absolute inset-0 bg-white p-6 pt-16 flex flex-col gap-3" hidden>
            <svg id="dieSvg" class="flex-1 w-full min-h-0" role="img" aria-label="Flat print layout"></svg>
            <p class="text-sm text-muted flex flex-wrap gap-5">
                <span><i class="inline-block w-7 align-middle border-t-2 border-ink mr-1"></i>Cut line</span>
                <span><i class="inline-block w-7 align-middle border-t-2 border-dashed border-packe-600 mr-1"></i>Fold line</span>
                <span id="dieDims" class="tabular-nums"></span>
            </p>
        </div>
        <div class="absolute left-4 top-4 flex gap-1 rounded-full bg-sand p-1 text-sm font-semibold" role="group" aria-label="Preview mode">
            <button type="button" id="tab3d" aria-pressed="true" class="rounded-full px-4 py-1.5 aria-pressed:bg-white aria-pressed:shadow">3D</button>
            <button type="button" id="tab2d" aria-pressed="false" class="rounded-full px-4 py-1.5 aria-pressed:bg-white aria-pressed:shadow">Flat layout</button>
        </div>
        <div id="foldbar" class="absolute left-4 right-4 bottom-4 flex flex-wrap items-center gap-3 rounded-xl bg-white/95 border border-line p-3 shadow">
            <?php if ($t['folds']): ?>
                <div class="flex gap-1" id="foldButtons">
                    <button type="button" class="btn btn-ghost !py-1.5 !px-3 text-sm" data-fold="0">Flat</button>
                    <button type="button" class="btn btn-ghost !py-1.5 !px-3 text-sm" data-fold="60">Open</button>
                    <button type="button" class="btn btn-ghost !py-1.5 !px-3 text-sm" data-fold="100">Closed</button>
                </div>
                <input type="range" id="fold" min="0" max="100" value="0" class="flex-1 min-w-[120px] accent-[#c2410c]" aria-label="Fold">
            <?php else: ?>
                <p class="flex-1 text-sm text-muted">Drag to turn it around. Scroll or pinch to zoom.</p>
            <?php endif; ?>
            <label class="text-sm text-muted flex items-center gap-1.5"><input type="checkbox" id="spin" class="accent-[#c2410c]" <?= $t['folds'] ? '' : 'checked' ?>> Spin</label>
        </div>
        <p id="gl-error" class="absolute inset-x-4 top-16 alert alert-error" hidden>Your browser could not start the 3D preview. You can still design and order; use the Flat layout view.</p>
    </section>

    <!-- Options form: posts to save_design_action.php (in the background, see js/cart.js) -->
    <form id="design-form" action="<?= url('actions/save_design_action.php') ?>" method="POST" enctype="multipart/form-data"
          class="card p-6 flex flex-col gap-6" novalidate data-validate="design" data-ajax-cart
          data-config='<?= e(json_encode($config)) ?>'>
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>">
        <div>
            <p class="label"><?= e($product['category_name']) ?></p>
            <h1 class="font-display font-extrabold text-2xl"><?= e($product['name']) ?></h1>
            <p class="text-muted text-sm mt-1"><?= e($t['blurb']) ?></p>
        </div>

        <fieldset class="flex flex-col gap-2">
            <legend class="label mb-2">Size</legend>
            <div class="grid gap-2" style="grid-template-columns: repeat(<?= count($t['fields']) ?>, minmax(0, 1fr))">
                <?php foreach ($t['fields'] as $k => [$label, $lo, $hi, $def]):
                    $pages = $type === 'catalog' && $k === 'height'; ?>
                    <label class="field"><span class="text-xs text-muted"><?= e($label) ?><?= $pages ? '' : ' (cm)' ?></span>
                        <input class="input tabular-nums" type="number" id="<?= $k ?>" name="<?= $k ?>" value="<?= $def ?>"
                               min="<?= $lo ?>" max="<?= $hi ?>" step="<?= $pages ? 4 : 0.1 ?>" required
                               data-label="<?= e($label) ?>" data-unit="<?= $pages ? '' : ' cm' ?>">
                    </label>
                <?php endforeach; ?>
            </div>
            <small class="field-error" data-error-for="length"></small>
            <div class="flex flex-wrap gap-2" id="presets">
                <?php foreach ($t['presets'] as [$name, $sizes]):
                    $txt = $type === 'catalog' ? $name : $name . ' ' . implode('×', $sizes); ?>
                    <button type="button" class="chip" data-size="<?= e(implode(',', $sizes)) ?>"><?= e($txt) ?></button>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset>
            <legend class="label mb-2">Material</legend>
            <div class="flex flex-wrap gap-2">
                <?php $first = true; foreach ($t['materials'] as $key => $name): ?>
                    <label class="chip cursor-pointer has-[:checked]:border-[#c2410c] has-[:checked]:shadow-[inset_0_0_0_1px_#c2410c]">
                        <input type="radio" name="material" value="<?= e($key) ?>" <?= $first ? 'checked' : '' ?> class="sr-only">
                        <span class="w-4 h-4 rounded-full border border-black/10" style="background: <?= $swatch[$key] ?>"></span><?= e($name) ?>
                    </label>
                <?php $first = false; endforeach; ?>
            </div>
        </fieldset>

        <fieldset>
            <legend class="label mb-2">Print colour</legend>
            <input type="hidden" name="print_colour" id="print_colour" value="">
            <div class="flex flex-wrap items-center gap-2" id="inks">
                <?php foreach ($inks as $hex => $name): ?>
                    <button type="button" class="w-9 h-9 rounded-full border-2 border-white ring-1 ring-line aria-pressed:ring-2 aria-pressed:ring-[#c2410c] <?= $hex === '' ? 'bg-[repeating-linear-gradient(45deg,#f3ede3_0_5px,#fff_5px_10px)]' : '' ?>"
                            style="<?= $hex ? 'background:' . $hex : '' ?>" data-ink="<?= $hex ?>" aria-pressed="<?= $hex === '' ? 'true' : 'false' ?>" aria-label="<?= $name ?>" title="<?= $name ?>"></button>
                <?php endforeach; ?>
                <label class="w-9 h-9 rounded-full overflow-hidden ring-1 ring-line cursor-pointer" title="Any colour"><span class="sr-only">Pick any colour</span>
                    <input type="color" id="customInk" value="#7a3fa0" class="w-12 h-12 -m-1.5 cursor-pointer"></label>
            </div>
        </fieldset>

        <fieldset class="flex flex-col gap-3">
            <legend class="label mb-2">Your branding</legend>
            <div class="rounded-xl border-2 border-dashed border-line p-3 flex items-center gap-3">
                <img id="logoThumb" alt="" class="w-12 h-12 object-contain bg-sand rounded-lg" hidden>
                <p id="logoMeta" class="flex-1 min-w-0 text-sm text-muted break-words">PNG, JPG or WebP up to 5 MB. Goes on the front.</p>
                <label for="logo" class="btn btn-ghost !py-1.5 text-sm cursor-pointer">Upload logo</label>
                <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp" class="sr-only">
            </div>
            <small class="field-error" data-error-for="logo"></small>
            <label class="flex items-center gap-3 text-sm text-muted">Logo size
                <input type="range" id="logoScale" min="20" max="90" value="55" class="flex-1 accent-[#c2410c]"></label>
            <label class="field"><span class="text-sm">Text <span class="text-muted">(optional)</span></span>
                <input class="input" type="text" id="brandText" name="brand_text" maxlength="28" placeholder="e.g. Thank you for your order">
                <small class="field-error" data-error-for="brand_text"></small></label>
            <div role="radiogroup" aria-labelledby="font-label">
                <p id="font-label" class="text-sm mb-2">Font</p>
                <div class="grid grid-cols-3 gap-2" id="fonts">
                    <?php foreach (DesignTypes::FONTS as $key => [$name, $family, $weight]): ?>
                        <label class="chip justify-center cursor-pointer !py-2 has-[:checked]:border-[#c2410c] has-[:checked]:shadow-[inset_0_0_0_1px_#c2410c] has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[#c2410c]">
                            <input type="radio" name="font" value="<?= e($key) ?>" class="sr-only" <?= $key === DesignTypes::DEFAULT_FONT ? 'checked' : '' ?>>
                            <span class="text-[17px] leading-tight" style="font-family: '<?= e($family) ?>', sans-serif; font-weight: <?= (int) $weight ?>"><?= e($name) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend class="label mb-2">Quantity</legend>
            <div class="grid grid-cols-5 gap-2" id="tiers">
                <?php foreach ($t['qtys'] as $q): ?>
                    <label class="chip !flex-col !gap-0 !px-1 text-center cursor-pointer has-[:checked]:border-[#c2410c] has-[:checked]:shadow-[inset_0_0_0_1px_#c2410c]">
                        <input type="radio" name="quantity" value="<?= $q ?>" class="sr-only" <?= $q === $defaultQty ? 'checked' : '' ?>>
                        <span class="font-semibold tabular-nums"><?= number_format($q) ?></span>
                        <small class="text-muted tabular-nums" data-tier-price="<?= $q ?>"></small>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="rounded-xl bg-sand p-4">
            <p class="font-display font-extrabold text-3xl tabular-nums" id="total">GH₵0.00</p>
            <p class="text-sm text-muted tabular-nums" id="unit"></p>
            <p class="text-xs text-muted mt-1">Final price is confirmed when you add the design to your cart.</p>
        </div>

        <button class="btn btn-primary text-base !py-3">Add design to cart</button>
        <a href="<?= url('views/cart.php') ?>" class="text-sm text-center text-packe-600 font-semibold hover:underline -mt-3">Go to cart</a>
    </form>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
