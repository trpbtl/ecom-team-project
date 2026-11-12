<?php
require_once __DIR__ . '/../../core/core.php';
require_login();
require_once __DIR__ . '/../../controllers/CustomerController.php';
// Accounts made with Google have no password yet: they set one here without a current password
$hasPassword = (new CustomerController())->hasPassword((int) $_SESSION['customer_id']);
$pageTitle = $hasPassword ? 'Change password' : 'Set a password';
require __DIR__ . '/../layout/header.php';
?>

<div class="max-w-md mx-auto">
    <a href="<?= url('views/account/my_account.php') ?>" class="text-sm text-muted hover:underline">← My account</a>
    <h1 class="font-display font-extrabold text-3xl mt-2 mb-2"><?= e($pageTitle) ?></h1>
    <?php if (!$hasPassword): ?>
        <p class="text-muted mb-6">You sign in with Google. Add a password if you'd also like to log in with your email.</p>
    <?php else: ?>
        <div class="mb-4"></div>
    <?php endif; ?>
    <form id="change-pass-form" action="<?= url('actions/change_pass_action.php') ?>" method="POST" class="card p-6 flex flex-col gap-4" novalidate data-validate="change-pass">
        <?= csrf_field() ?>
        <?php if ($hasPassword): ?>
            <label class="field"><span>Current password</span>
                <input class="input" type="password" name="current_password" autocomplete="current-password" maxlength="72" required>
                <small class="field-error" data-error-for="current_password"></small></label>
        <?php endif; ?>
        <label class="field"><span>New password</span>
            <input class="input" type="password" name="new_password" autocomplete="new-password" maxlength="72" required>
            <small class="field-error" data-error-for="new_password"></small></label>
        <label class="field"><span>Confirm new password</span>
            <input class="input" type="password" name="confirm_password" autocomplete="new-password" maxlength="72" required>
            <small class="field-error" data-error-for="confirm_password"></small></label>
        <p class="text-sm text-muted">At least 8 characters, including a number.</p>
        <button class="btn btn-primary"><?= e($pageTitle) ?></button>
    </form>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
