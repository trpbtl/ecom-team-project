<?php
require_once __DIR__ . '/../../core/core.php';
require_admin();
require_once __DIR__ . '/../../controllers/ProductController.php';

$pc = new ProductController();
$editing = isset($_GET['edit_id']) ? $pc->getProductById($_GET['edit_id']) : null;
$categories = $pc->getAllCategories();
$products = $pc->getAllProducts();
// Form values: the product being edited, or what was typed before a validation error
$v = fn(string $k, $default = '') => e($editing[$k] ?? ($_SESSION['old'][$k] ?? $default));
$pageTitle = 'Products';
require __DIR__ . '/../layout/header.php';
?>

<h1 class="font-display font-extrabold text-3xl mb-6">Products</h1>

<div class="grid xl:grid-cols-[420px_1fr] gap-6 items-start">
    <form id="product-form" action="<?= url($editing ? 'actions/update_product_action.php' : 'actions/add_product_action.php') ?>" method="POST" enctype="multipart/form-data"
          class="card p-6 flex flex-col gap-4" novalidate data-validate="product">
        <?= csrf_field() ?>
        <h2 class="font-display font-bold text-xl"><?= $editing ? 'Edit product' : 'Add a product' ?></h2>
        <?php if ($editing): ?><input type="hidden" name="product_id" value="<?= (int) $editing['product_id'] ?>"><?php endif; ?>

        <label class="field"><span>Product name</span>
            <input class="input" name="name" value="<?= $v('name') ?>" maxlength="150" required>
            <small class="field-error" data-error-for="name"></small></label>
        <div class="grid grid-cols-2 gap-3">
            <label class="field"><span>Category</span>
                <select class="input" name="category_id" required>
                    <option value="">Choose…</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['category_id'] ?>" <?= (string) $c['category_id'] === html_entity_decode($v('category_id')) ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="field-error" data-error-for="category_id"></small></label>
            <label class="field"><span>Price (GH₵)</span>
                <input class="input tabular-nums" type="number" name="base_price" value="<?= $v('base_price') ?>" min="0.01" max="100000" step="0.01" required>
                <small class="field-error" data-error-for="base_price"></small></label>
        </div>
        <label class="field"><span>Material</span>
            <input class="input" name="material" value="<?= $v('material') ?>" maxlength="100" placeholder="e.g. Corrugated cardboard"></label>
        <label class="field"><span>Description</span>
            <textarea class="input" name="description" rows="4" maxlength="2000"><?= $v('description') ?></textarea>
            <small class="field-error" data-error-for="description"></small></label>
        <label class="field"><span>Keywords <span class="text-muted">(for search)</span></span>
            <input class="input" name="keywords" value="<?= $v('keywords') ?>" maxlength="255" placeholder="box, mailer, shipping"></label>
        <label class="field"><span>3D designer <span class="text-muted">(customers can design their own version)</span></span>
            <select class="input" name="design_type">
                <option value="">None: sold ready-made only</option>
                <?php foreach (DesignTypes::options() as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $key === html_entity_decode($v('design_type')) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <span class="text-xs text-muted">The price above is for the plain product. Designs are priced from size, material, print and quantity.</span></label>
        <label class="field"><span>Image <span class="text-muted">(JPG, PNG, GIF or WebP, up to 2 MB)</span></span>
            <?php if ($editing && $editing['image']): ?>
                <img src="<?= product_image_url($editing['image']) ?>" alt="Current image" class="w-32 h-24 object-cover rounded-lg border border-line">
                <span class="text-xs text-muted">Leave empty to keep the current image.</span>
            <?php endif; ?>
            <input class="input" type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
            <small class="field-error" data-error-for="image"></small></label>
        <div class="flex gap-3">
            <button class="btn btn-primary"><?= $editing ? 'Save changes' : 'Add product' ?></button>
            <?php if ($editing): ?><a href="<?= url('views/admin/product.php') ?>" class="btn btn-ghost">Cancel</a><?php endif; ?>
        </div>
    </form>

    <section class="card p-6 min-w-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-muted"><th class="py-2">Product</th><th>Category</th><th class="text-right">Price</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                    <tr class="border-t border-line">
                        <td class="py-3">
                            <div class="flex items-center gap-3">
                                <img src="<?= product_image_url($p['image']) ?>" alt="" class="w-12 h-10 object-cover rounded">
                                <span class="font-medium"><?= e($p['name']) ?><?= DesignTypes::get($p['design_type']) ? ' <span class="badge status-processing">3D</span>' : '' ?></span>
                            </div>
                        </td>
                        <td><?= e($p['category_name']) ?></td>
                        <td class="text-right tabular-nums"><?= money($p['base_price']) ?></td>
                        <td class="text-right"><a href="<?= url('views/admin/product.php?edit_id=' . (int) $p['product_id']) ?>" class="text-packe-600 font-semibold hover:underline">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php forget_input(); require __DIR__ . '/../layout/footer.php'; ?>
