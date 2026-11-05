<?php
require_once __DIR__ . '/../../core/core.php';
require_admin();
require_once __DIR__ . '/../../controllers/ProductController.php';

$pc = new ProductController();
$editing = isset($_GET['edit_id']) ? $pc->getCategoryById($_GET['edit_id']) : null;
$categories = $pc->getAllCategories();
$pageTitle = 'Categories';
require __DIR__ . '/../layout/header.php';
?>

<h1 class="font-display font-extrabold text-3xl mb-6">Categories</h1>

<div class="grid lg:grid-cols-[360px_1fr] gap-6 items-start">
    <form id="category-form" action="<?= url($editing ? 'actions/update_category_action.php' : 'actions/add_category_action.php') ?>" method="POST"
          class="card p-6 flex flex-col gap-4" novalidate data-validate="category">
        <?= csrf_field() ?>
        <h2 class="font-display font-bold text-xl"><?= $editing ? 'Edit category' : 'Add a category' ?></h2>
        <?php if ($editing): ?><input type="hidden" name="category_id" value="<?= (int) $editing['category_id'] ?>"><?php endif; ?>
        <label class="field"><span>Category name</span>
            <input class="input" name="category_name" value="<?= e($editing['category_name'] ?? '') ?>" maxlength="60" required>
            <small class="field-error" data-error-for="category_name"></small></label>
        <div class="flex gap-3">
            <button class="btn btn-primary"><?= $editing ? 'Save changes' : 'Add category' ?></button>
            <?php if ($editing): ?><a href="<?= url('views/admin/category.php') ?>" class="btn btn-ghost">Cancel</a><?php endif; ?>
        </div>
    </form>

    <section class="card p-6 min-w-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-muted"><th class="py-2">Name</th><th class="text-right">Products</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr class="border-t border-line">
                        <td class="py-3 font-medium"><?= e($c['category_name']) ?></td>
                        <td class="text-right tabular-nums"><?= (int) $c['product_count'] ?></td>
                        <td class="text-right"><a href="<?= url('views/admin/category.php?edit_id=' . (int) $c['category_id']) ?>" class="text-packe-600 font-semibold hover:underline">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
