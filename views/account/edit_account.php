<?php
require_once __DIR__ . '/../../core/core.php';
require_login();
require_once __DIR__ . '/../../controllers/CustomerController.php';

$me = (new CustomerController())->getCustomer((int) $_SESSION['customer_id']);
$pageTitle = 'Edit my details';
require __DIR__ . '/../layout/header.php';
?>

<div class="max-w-2xl mx-auto">
    <a href="<?= url('views/account/my_account.php') ?>" class="text-sm text-muted hover:underline">← My account</a>
    <h1 class="font-display font-extrabold text-3xl mt-2 mb-6">Edit my details</h1>
    <form id="edit-account-form" action="<?= url('actions/edit_account_action.php') ?>" method="POST" class="card p-6 grid sm:grid-cols-2 gap-4" novalidate data-validate="profile">
        <?= csrf_field() ?>
        <label class="field sm:col-span-2"><span>Full name</span>
            <input class="input" name="full_name" value="<?= e($me['full_name']) ?>" maxlength="100" required>
            <small class="field-error" data-error-for="full_name"></small></label>
        <label class="field"><span>Email</span>
            <input class="input" type="email" name="email" value="<?= e($me['email']) ?>" maxlength="100" required>
            <small class="field-error" data-error-for="email"></small></label>
        <label class="field"><span>Phone number</span>
            <input class="input" type="tel" name="phone" value="<?= e($me['phone']) ?>" maxlength="15" required>
            <small class="field-error" data-error-for="phone"></small></label>
        <label class="field"><span>Business name <span class="text-muted">(optional)</span></span>
            <input class="input" name="business_name" value="<?= e($me['business_name']) ?>" maxlength="150">
            <small class="field-error" data-error-for="business_name"></small></label>
        <label class="field"><span>City</span>
            <input class="input" name="city" value="<?= e($me['city']) ?>" maxlength="60" required>
            <small class="field-error" data-error-for="city"></small></label>
        <label class="field sm:col-span-2"><span>Address <span class="text-muted">(optional)</span></span>
            <input class="input" name="address" value="<?= e($me['address']) ?>" maxlength="255">
            <small class="field-error" data-error-for="address"></small></label>
        <div class="sm:col-span-2 flex justify-end gap-3">
            <a href="<?= url('views/account/my_account.php') ?>" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary">Save changes</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
