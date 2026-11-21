<?php
require_once __DIR__ . '/../../core/core.php';
require_admin();
require_once __DIR__ . '/../../controllers/BusinessController.php';

$bc = new BusinessController();
$search = Validate::clean($_GET['q'] ?? '');
$roleFilter = (int) ($_GET['role'] ?? 0);
$editing = isset($_GET['edit_id']) ? $bc->getById((int) $_GET['edit_id']) : null;
$businesses = $bc->getAll($search, $roleFilter);
$roles = BusinessController::roles();
$me = (int) $_SESSION['customer_id'];
$pageTitle = 'Businesses';
require __DIR__ . '/../layout/header.php';

$v = fn(string $f) => $editing ? e($editing[$f] ?? '') : old($f);
$currentRole = $editing ? (int) $editing['user_role'] : (int) ($_SESSION['old']['user_role'] ?? 2);
?>

<h1 class="font-display font-extrabold text-3xl mb-6">Businesses &amp; accounts</h1>

<div class="grid lg:grid-cols-[380px_1fr] gap-6 items-start">
    <form action="<?= url($editing ? 'actions/update_business_action.php' : 'actions/add_business_action.php') ?>" method="POST"
          class="card p-6 flex flex-col gap-4" novalidate>
        <?= csrf_field() ?>
        <h2 class="font-display font-bold text-xl"><?= $editing ? 'Edit business' : 'Add a business' ?></h2>
        <?php if ($editing): ?><input type="hidden" name="customer_id" value="<?= (int) $editing['customer_id'] ?>"><?php endif; ?>

        <label class="field"><span>Business name</span>
            <input class="input" name="business_name" value="<?= $v('business_name') ?>" maxlength="150"></label>
        <label class="field"><span>Contact person</span>
            <input class="input" name="full_name" value="<?= $v('full_name') ?>" maxlength="100" required></label>
        <label class="field"><span>Email</span>
            <input class="input" type="email" name="email" value="<?= $v('email') ?>" maxlength="100" required></label>
        <label class="field"><span>Phone</span>
            <input class="input" name="phone" value="<?= $v('phone') ?>" maxlength="20" required></label>
        <label class="field"><span>City</span>
            <input class="input" name="city" value="<?= $v('city') ?>" maxlength="60" required></label>
        <label class="field"><span>Address</span>
            <input class="input" name="address" value="<?= $v('address') ?>" maxlength="255"></label>
        <label class="field"><span>Role</span>
            <select class="input" name="user_role">
                <?php foreach ($roles as $id => $label): ?>
                    <option value="<?= $id ?>" <?= $currentRole === $id ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select></label>
        <label class="field"><span><?= $editing ? 'New password (leave empty to keep the current one)' : 'Password' ?></span>
            <input class="input" type="password" name="password" autocomplete="new-password" <?= $editing ? '' : 'required' ?>>
            <small class="text-muted">At least 8 characters, with a number.</small></label>

        <div class="flex gap-3">
            <button class="btn btn-primary"><?= $editing ? 'Save changes' : 'Add business' ?></button>
            <?php if ($editing): ?><a href="<?= url('views/admin/businesses.php') ?>" class="btn btn-ghost">Cancel</a><?php endif; ?>
        </div>
    </form>

    <section class="card p-6 min-w-0">
        <form method="GET" class="flex flex-wrap gap-3 mb-4">
            <input class="input flex-1 min-w-[180px]" name="q" value="<?= e($search) ?>" placeholder="Search name, business, email, phone, city">
            <select class="input" name="role">
                <option value="0">All roles</option>
                <?php foreach ($roles as $id => $label): ?>
                    <option value="<?= $id ?>" <?= $roleFilter === $id ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-ghost">Filter</button>
        </form>

        <?php if (!$businesses): ?>
            <p class="text-muted">No accounts found.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-muted">
                    <th class="py-2">Business</th><th>Contact</th><th>City</th><th>Role</th><th class="text-right">Orders</th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($businesses as $b): $bid = (int) $b['customer_id']; ?>
                    <tr class="border-t border-line align-top">
                        <td class="py-3">
                            <div class="font-medium"><?= e($b['business_name'] ?: '—') ?></div>
                            <div class="text-muted text-xs">Joined <?= e(date('j M Y', strtotime($b['created_at']))) ?><?= $b['google_only'] ? ' · Google sign-in' : '' ?></div>
                        </td>
                        <td>
                            <div><?= e($b['full_name']) ?></div>
                            <div class="text-muted text-xs"><?= e($b['email']) ?> · <?= e($b['phone']) ?></div>
                        </td>
                        <td><?= e($b['city']) ?></td>
                        <td>
                            <form action="<?= url('actions/set_business_role_action.php') ?>" method="POST" class="flex gap-1">
                                <?= csrf_field() ?>
                                <input type="hidden" name="customer_id" value="<?= $bid ?>">
                                <select class="input !py-1 text-xs" name="user_role" onchange="this.form.submit()" <?= $bid === $me ? 'disabled title="You cannot change your own role"' : '' ?>>
                                    <?php foreach ($roles as $id => $label): ?>
                                        <option value="<?= $id ?>" <?= (int) $b['user_role'] === $id ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <noscript><button class="btn btn-ghost">Save</button></noscript>
                            </form>
                        </td>
                        <td class="text-right tabular-nums"><?= (int) $b['order_count'] ?></td>
                        <td class="text-right whitespace-nowrap">
                            <a href="<?= url('views/admin/businesses.php?edit_id=' . $bid) ?>" class="text-packe-600 font-semibold hover:underline">Edit</a>
                            <?php if ($bid !== $me): ?>
                                <form action="<?= url('actions/delete_business_action.php') ?>" method="POST" class="inline"
                                      onsubmit="return confirm('Delete <?= e(addslashes($b['business_name'] ?: $b['full_name'])) ?>? This cannot be undone.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="customer_id" value="<?= $bid ?>">
                                    <button class="text-red-600 font-semibold hover:underline ml-3">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
</div>

<?php forget_input(); require __DIR__ . '/../layout/footer.php'; ?>