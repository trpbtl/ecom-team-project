<?php
require_once __DIR__ . '/../../core/core.php';
require_login();
$pageTitle = 'Change password';
require __DIR__ . '/../layout/header.php';
?>

<div class="max-w-md mx-auto">
    <a href="<?= url('views/account/my_account.php') ?>" class="text-sm text-muted hover:underline">← My account</a>
    <h1 class="font-display font-extrabold text-3xl mt-2 mb-6">Change password</h1>
    <form id="change-pass-form" action="<?= url('actions/change_pass_action.php') ?>" method="POST" class="card p-6 flex flex-col gap-4" novalidate data-validate="change-pass">
        <?= csrf_field() ?>
        <label class="field"><span>Current password</span>
            <input class="input" type="password" name="current_password" autocomplete="current-password" maxlength="72" required>
            <small class="field-error" data-error-for="current_password"></small></label>
        <label class="field"><span>New password</span>
            <input class="input" type="password" name="new_password" autocomplete="new-password" maxlength="72" required>
            <small class="field-error" data-error-for="new_password"></small></label>
        <label class="field"><span>Confirm new password</span>
            <input class="input" type="password" name="confirm_password" autocomplete="new-password" maxlength="72" required>
            <small class="field-error" data-error-for="confirm_password"></small></label>
        <p class="text-sm text-muted">At least 8 characters, including a number.</p>
        <button class="btn btn-primary">Change password</button>
    </form>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
