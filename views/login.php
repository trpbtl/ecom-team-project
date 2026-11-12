<?php
require_once __DIR__ . '/../core/core.php';
if (is_logged_in()) redirect('index.php');
$pageTitle = 'Log in';
require __DIR__ . '/layout/header.php';
?>

<div class="max-w-md mx-auto">
    <h1 class="font-display font-extrabold text-3xl">Log in</h1>
    <p class="text-muted mt-1 mb-6">Welcome back. Your cart will be kept.</p>
    <form id="login-form" action="<?= url('actions/login_action.php') ?>" method="POST" class="card p-6 flex flex-col gap-4" novalidate data-validate="login">
        <?= csrf_field() ?>
        <?php include __DIR__ . '/partials/google_button.php'; ?>
        <label class="field"><span>Email</span>
            <input class="input" type="email" name="email" value="<?= old('email') ?>" maxlength="100" autocomplete="email" required>
            <small class="field-error" data-error-for="email"></small></label>
        <label class="field"><span>Password</span>
            <input class="input" type="password" name="password" maxlength="72" autocomplete="current-password" required>
            <small class="field-error" data-error-for="password"></small></label>
        <button class="btn btn-primary w-full">Log in</button>
        <p class="text-sm text-muted text-center">New to Packe? <a href="<?= url('views/register.php') ?>" class="text-packe-600 font-semibold hover:underline">Create an account</a></p>
    </form>
</div>

<?php forget_input(); require __DIR__ . '/layout/footer.php'; ?>
