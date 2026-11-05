<?php
require_once __DIR__ . '/../core/core.php';
if (is_logged_in()) redirect('views/account/my_account.php');
$pageTitle = 'Create an account';
require __DIR__ . '/layout/header.php';
?>

<div class="max-w-2xl mx-auto">
    <h1 class="font-display font-extrabold text-3xl">Create your Packe account</h1>
    <p class="text-muted mt-1 mb-6">Save your box designs, check out faster and track every order.</p>

    <form id="register-form" action="<?= url('actions/register_action.php') ?>" method="POST" class="card p-6 grid sm:grid-cols-2 gap-4" novalidate data-validate="register">
        <?= csrf_field() ?>
        <label class="field sm:col-span-2"><span>Full name</span>
            <input class="input" name="full_name" value="<?= old('full_name') ?>" maxlength="100" autocomplete="name" required>
            <small class="field-error" data-error-for="full_name"></small></label>
        <label class="field"><span>Email</span>
            <input class="input" type="email" name="email" value="<?= old('email') ?>" maxlength="100" autocomplete="email" required>
            <small class="field-error" data-error-for="email"></small></label>
        <label class="field"><span>Phone number</span>
            <input class="input" type="tel" name="phone" value="<?= old('phone') ?>" maxlength="15" autocomplete="tel" placeholder="024 123 4567" required>
            <small class="field-error" data-error-for="phone"></small></label>
        <label class="field"><span>Password</span>
            <input class="input" type="password" name="password" maxlength="72" autocomplete="new-password" required>
            <small class="field-error" data-error-for="password"></small></label>
        <label class="field"><span>Confirm password</span>
            <input class="input" type="password" name="confirm_password" maxlength="72" autocomplete="new-password" required>
            <small class="field-error" data-error-for="confirm_password"></small></label>
        <label class="field"><span>Business name <span class="text-muted">(optional)</span></span>
            <input class="input" name="business_name" value="<?= old('business_name') ?>" maxlength="150" autocomplete="organization">
            <small class="field-error" data-error-for="business_name"></small></label>
        <label class="field"><span>City</span>
            <input class="input" name="city" value="<?= old('city') ?>" maxlength="60" list="gh-cities" autocomplete="address-level2" required>
            <small class="field-error" data-error-for="city"></small></label>
        <label class="field sm:col-span-2"><span>Address <span class="text-muted">(optional, used for delivery)</span></span>
            <input class="input" name="address" value="<?= old('address') ?>" maxlength="255" autocomplete="street-address">
            <small class="field-error" data-error-for="address"></small></label>
        <div class="sm:col-span-2 flex flex-wrap items-center justify-between gap-3 pt-2">
            <p class="text-sm text-muted">Already have an account? <a href="<?= url('views/login.php') ?>" class="text-packe-600 font-semibold hover:underline">Log in</a></p>
            <button class="btn btn-primary">Create account</button>
        </div>
    </form>
</div>
<datalist id="gh-cities">
    <option>Accra</option><option>Kumasi</option><option>Tema</option><option>Takoradi</option><option>Cape Coast</option>
    <option>Tamale</option><option>Ho</option><option>Koforidua</option><option>Sunyani</option><option>Kasoa</option>
</datalist>

<?php forget_input(); require __DIR__ . '/layout/footer.php'; ?>
