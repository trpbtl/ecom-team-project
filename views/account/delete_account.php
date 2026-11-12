<?php
require_once __DIR__ . '/../../core/core.php';
require_login();
require_once __DIR__ . '/../../controllers/CustomerController.php';
$hasPassword = (new CustomerController())->hasPassword((int) $_SESSION['customer_id']);   // Google-only accounts just type DELETE
$pageTitle = 'Delete my account';
require __DIR__ . '/../layout/header.php';
?>

<div class="max-w-md mx-auto">
    <a href="<?= url('views/account/my_account.php') ?>" class="text-sm text-muted hover:underline">← My account</a>
    <h1 class="font-display font-extrabold text-3xl mt-2">Delete my account</h1>
    <p class="text-muted mt-2 mb-6">This removes your account, saved designs and cart. It can't be undone. Accounts with orders are kept for our records.</p>
    <form id="delete-account-form" action="<?= url('actions/delete_account_action.php') ?>" method="POST" class="card p-6 flex flex-col gap-4" novalidate data-validate="delete-account">
        <?= csrf_field() ?>
        <?php if ($hasPassword): ?>
            <label class="field"><span>Your password</span>
                <input class="input" type="password" name="password" autocomplete="current-password" maxlength="72" required>
                <small class="field-error" data-error-for="password"></small></label>
        <?php endif; ?>
        <label class="field"><span>Type <b>DELETE</b> to confirm</span>
            <input class="input" name="confirm" autocomplete="off" required>
            <small class="field-error" data-error-for="confirm"></small></label>
        <div class="flex gap-3 justify-end">
            <a href="<?= url('views/account/my_account.php') ?>" class="btn btn-ghost">Keep my account</a>
            <button class="btn btn-danger">Delete account</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
