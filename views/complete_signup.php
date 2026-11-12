<?php
// After "Continue with Google" for someone new to Packe: Google gave their name and email;
// orders also need a phone number and city, so they add those here once.
require_once __DIR__ . '/../core/core.php';
if (is_logged_in()) redirect('index.php');

$p = $_SESSION['social_pending'] ?? null;
if (!$p || time() - $p['time'] > 1800) {
    unset($_SESSION['social_pending']);
    flash('error', 'Please continue with Google again to create your account.');
    redirect('views/login.php');
}
$emailLocked = $p['email'] !== '' && $p['email_verified'];
$pageTitle = 'Finish signing up';
require __DIR__ . '/layout/header.php';
?>

<div class="max-w-2xl mx-auto">
    <h1 class="font-display font-extrabold text-3xl">Almost there<?= $p['name'] !== '' ? ', ' . e(explode(' ', $p['name'])[0]) : '' ?></h1>
    <p class="text-muted mt-1 mb-6">You're signing up with Google. Add your phone number and city so we can deliver your orders.</p>

    <form id="complete-signup-form" action="<?= url('actions/complete_signup_action.php') ?>" method="POST" class="card p-6 grid sm:grid-cols-2 gap-4" novalidate data-validate="profile">
        <?= csrf_field() ?>
        <label class="field sm:col-span-2"><span>Full name</span>
            <input class="input" name="full_name" value="<?= old('full_name', $p['name']) ?>" maxlength="100" autocomplete="name" required>
            <small class="field-error" data-error-for="full_name"></small></label>
        <label class="field"><span>Email</span>
            <input class="input <?= $emailLocked ? 'bg-sand text-muted' : '' ?>" type="email" name="email" value="<?= $emailLocked ? e($p['email']) : old('email', $p['email']) ?>"
                   maxlength="100" autocomplete="email" required <?= $emailLocked ? 'readonly' : '' ?>>
            <?php if ($emailLocked): ?><span class="text-xs text-muted">From your Google account</span><?php endif; ?>
            <small class="field-error" data-error-for="email"></small></label>
        <label class="field"><span>Phone number</span>
            <input class="input" type="tel" name="phone" value="<?= old('phone') ?>" maxlength="15" autocomplete="tel" placeholder="024 123 4567" required>
            <small class="field-error" data-error-for="phone"></small></label>
        <label class="field"><span>City</span>
            <input class="input" name="city" value="<?= old('city') ?>" maxlength="60" autocomplete="address-level2" placeholder="Accra" required>
            <small class="field-error" data-error-for="city"></small></label>
        <label class="field"><span>Business name <span class="text-muted">(optional)</span></span>
            <input class="input" name="business_name" value="<?= old('business_name') ?>" maxlength="150" autocomplete="organization">
            <small class="field-error" data-error-for="business_name"></small></label>
        <label class="field sm:col-span-2"><span>Address <span class="text-muted">(optional)</span></span>
            <input class="input" name="address" value="<?= old('address') ?>" maxlength="255" autocomplete="street-address">
            <small class="field-error" data-error-for="address"></small></label>
        <div class="sm:col-span-2 flex flex-wrap items-center justify-between gap-3">
            <a href="<?= url('views/login.php') ?>" class="text-sm text-muted hover:underline">Cancel</a>
            <button class="btn btn-primary">Create my account</button>
        </div>
    </form>
</div>

<?php forget_input(); require __DIR__ . '/layout/footer.php'; ?>
