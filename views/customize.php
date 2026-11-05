<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../controllers/CustomizationController.php';

$pc = new ProductController();
$product = isset($_GET['product_id']) ? $pc->getProductById($_GET['product_id']) : null;
if (!$product) {   // default to the first product that can be designed
    foreach ($pc->getAllProducts() as $p) {
        if ($p['is_customizable']) { $product = $p; break; }
    }
}
if (!$product || !$product['is_customizable']) {
    flash('error', 'There is no product to design yet.');
    redirect('views/all_products.php');
}
$limits = CustomizationController::LIMITS;
$tiers = CustomizationController::TIERS;
$inks = ['' => 'No print colour', '#d14615' => 'Packe orange', '#1c1a18' => 'Black', '#1f4e79' => 'Navy', '#2f6b3a' => 'Green', '#e8b4b8' => 'Blush pink'];
$pageTitle = 'Design your ' . $product['name'];
$extraScripts = '<script type="importmap">{"imports":{"three":"https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js","three/addons/":"https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/"}}</script>'
              . '<script type="module" src="' . url('js/designer.js') . '"></script>';
require __DIR__ . '/layout/header.php';
?>

<div class="grid lg:grid-cols-[minmax(0,1fr)_400px] gap-6 items-start">
    <!-- 3D stage -->
    <section class="lg:sticky lg:top-24 rounded-2xl border border-line overflow-hidden relative h-[60vh] lg:h-[calc(100vh-8rem)] min-h-[420px] bg-[radial-gradient(ellipse_at_50%_40%,#ffffff_0%,#e9e3d9_100%)]" aria-label="Box preview">
        <canvas id="gl" class="absolute inset-0 w-full h-full touch-none cursor-grab" aria-label="3D preview of your box"></canvas>
        <div id="dieline" class="absolute inset-0 bg-white p-6 flex flex-col gap-3" hidden>
            <svg id="dieSvg" class="flex-1 w-full min-h-0" role="img" aria-label="Flat dieline of the box"></svg>
            <p class="text-sm text-muted flex flex-wrap gap-5">
                <span><i class="inline-block w-7 align-middle border-t-2 border-ink mr-1"></i>Cut line</span>
                <span><i class="inline-block w-7 align-middle border-t-2 border-dashed border-packe-600 mr-1"></i>Fold line</span>
                <span id="dieDims" class="tabular-nums"></span>
            </p>
        </div>
        <div class="absolute left-4 top-4 flex gap-1 rounded-full bg-sand p-1 text-sm font-semibold" role="group" aria-label="Preview mode">
            <button type="button" id="tab3d" aria-pressed="true" class="rounded-full px-4 py-1.5 aria-pressed:bg-white aria-pressed:shadow">3D</button>
            <button type="button" id="tab2d" aria-pressed="false" class="rounded-full px-4 py-1.5 aria-pressed:bg-white aria-pressed:shadow">Flat dieline</button>
        </div>
        <div id="foldbar" class="absolute left-4 right-4 bottom-4 flex flex-wrap items-center gap-3 rounded-xl bg-white/95 border border-line p-3 shadow">
            <div class="flex gap-1">
                <button type="button" class="btn btn-ghost !py-1.5 !px-3 text-sm" data-fold="0">Flat</button>
                <button type="button" class="btn btn-ghost !py-1.5 !px-3 text-sm" data-fold="60">Open</button>
                <button type="button" class="btn btn-ghost !py-1.5 !px-3 text-sm" data-fold="100">Closed</button>
            </div>
            <input type="range" id="fold" min="0" max="100" value="0" class="flex-1 min-w-[120px] accent-[#c2410c]" aria-label="Fold the box">
            <label class="text-sm text-muted flex items-center gap-1.5"><input type="checkbox" id="spin" class="accent-[#c2410c]"> Spin</label>
        </div>
        <p id="gl-error" class="absolute inset-x-4 top-16 alert alert-error" hidden>Your browser could not start the 3D preview. You can still design and order; use the Flat dieline view.</p>
    </section>

    <!-- Options form: posts to save_design_action.php -->
    <form id="design-form" action="<?= url('actions/save_design_action.php') ?>" method="POST" enctype="multipart/form-data"
          class="card p-6 flex flex-col gap-6" novalidate data-validate="design"
          data-base-price="<?= e($product['base_price']) ?>" data-tiers='<?= e(json_encode($tiers)) ?>'>
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>">
        <div>
            <p class="label"><?= e($product['category_name']) ?></p>
            <h1 class="font-display font-extrabold text-2xl"><?= e($product['name']) ?></h1>
            <p class="text-muted text-sm mt-1">Set the size, pick a board, add your logo, and watch it fold up in 3D.</p>
        </div>

        <fieldset class="flex flex-col gap-2">
            <legend class="label mb-2">Size (cm)</legend>
            <div class="grid grid-cols-3 gap-2">
                <?php foreach (['length' => 30, 'width' => 22, 'height' => 10] as $k => $v): [$lo, $hi] = $limits[$k]; ?>
                    <label class="field"><span class="text-xs text-muted"><?= ucfirst($k) ?></span>
                        <input class="input tabular-nums" type="number" id="<?= $k ?>" name="<?= $k ?>" value="<?= $v ?>" min="<?= $lo ?>" max="<?= $hi ?>" step="0.5" required>
                    </label>
                <?php endforeach; ?>
            </div>
            <small class="field-error" data-error-for="length"></small>
            <div class="flex flex-wrap gap-2" id="presets">
                <button type="button" class="chip" data-size="20,15,6">Small 20×15×6</button>
                <button type="button" class="chip" data-size="30,22,10">Medium 30×22×10</button>
                <button type="button" class="chip" data-size="40,30,15">Large 40×30×15</button>
            </div>
        </fieldset>

        <fieldset>
            <legend class="label mb-2">Board</legend>
            <div class="flex gap-2">
                <label class="chip cursor-pointer has-[:checked]:chip-active has-[:checked]:border-[#c2410c]"><input type="radio" name="material" value="kraft" checked class="sr-only"><span class="w-4 h-4 rounded-full bg-[#c79c6b] border border-black/10"></span>Kraft</label>
                <label class="chip cursor-pointer has-[:checked]:border-[#c2410c]"><input type="radio" name="material" value="white" class="sr-only"><span class="w-4 h-4 rounded-full bg-[#f4f2ee] border border-black/10"></span>White</label>
            </div>
        </fieldset>

        <fieldset>
            <legend class="label mb-2">Print colour (outside)</legend>
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
                <p id="logoMeta" class="flex-1 min-w-0 text-sm text-muted break-words">PNG, JPG or WebP up to 5 MB. Goes on the lid and front.</p>
                <label for="logo" class="btn btn-ghost !py-1.5 text-sm cursor-pointer">Upload logo</label>
                <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp" class="sr-only">
            </div>
            <small class="field-error" data-error-for="logo"></small>
            <label class="flex items-center gap-3 text-sm text-muted">Logo size
                <input type="range" id="logoScale" min="20" max="90" value="55" class="flex-1 accent-[#c2410c]"></label>
            <label class="field"><span class="text-sm">Text on the box <span class="text-muted">(optional)</span></span>
                <input class="input" type="text" id="brandText" name="brand_text" maxlength="28" placeholder="e.g. Thank you for your order">
                <small class="field-error" data-error-for="brand_text"></small></label>
        </fieldset>

        <fieldset>
            <legend class="label mb-2">Quantity</legend>
            <div class="grid grid-cols-5 gap-2" id="tiers">
                <?php foreach (array_keys($tiers) as $i => $q): ?>
                    <label class="chip !flex-col !gap-0 !px-1 text-center cursor-pointer has-[:checked]:border-[#c2410c] has-[:checked]:shadow-[inset_0_0_0_1px_#c2410c]">
                        <input type="radio" name="quantity" value="<?= $q ?>" class="sr-only" <?= $q === 100 ? 'checked' : '' ?>>
                        <span class="font-semibold tabular-nums"><?= $q ?></span>
                        <small class="text-muted tabular-nums" data-tier-price="<?= $q ?>"></small>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="rounded-xl bg-sand p-4">
            <p class="font-display font-extrabold text-3xl tabular-nums" id="total">GH₵0.00</p>
            <p class="text-sm text-muted tabular-nums" id="unit"></p>
            <p class="text-xs text-muted mt-1">Final price is confirmed when you save the design.</p>
        </div>

        <button class="btn btn-primary text-base !py-3">Save design and add to cart</button>
    </form>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
