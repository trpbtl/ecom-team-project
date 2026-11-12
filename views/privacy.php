<?php
// Privacy policy (linked from the footer, and from the Google sign-in consent screen).
require_once __DIR__ . '/../core/core.php';
$pageTitle = 'Privacy policy';
$updated = '12 November 2026';
require __DIR__ . '/layout/header.php';
?>

<article class="max-w-3xl mx-auto prose-packe">
    <h1 class="font-display font-extrabold text-4xl">Privacy policy</h1>
    <p class="text-muted mt-1">Last updated <?= e($updated) ?></p>

    <p class="mt-6">Packe is an online store where businesses in Ghana design and order custom packaging. This page explains what
        information we collect, why, and the choices you have. Packe is a university e-commerce team project; payments run in
        Paystack's test mode, so no real money is taken.</p>

    <h2>What we collect</h2>
    <ul>
        <li><b>Account details:</b> your name, email address, phone number, city, and (if you add them) your business name and address.
            If you create a password, we store only a secure hash of it, never the password itself.</li>
        <li><b>Sign in with Google:</b> if you choose "Continue with Google", Google shares your name, email address, whether that email
            is verified, and a Google account ID with us. We use them only to create your account and sign you in. We do not receive
            your Google password, contacts, files or anything else from your Google account.</li>
        <li><b>Designs and orders:</b> the packaging you design (sizes, colours, patterns, text and any logo you upload), your cart,
            orders, and the delivery details you give at checkout (recipient name, phone number and address).</li>
        <li><b>Payments:</b> payments are handled by <a href="https://paystack.com/terms" target="_blank" rel="noopener">Paystack</a>.
            Your card or mobile money details go to Paystack, not to us. We keep only the payment reference, amount and status.</li>
    </ul>

    <h2>How we use it</h2>
    <ul>
        <li>To run your account, save your designs, and process and deliver your orders.</li>
        <li>To contact you about an order or delivery.</li>
        <li>To keep the site secure, for example to stop forged requests and sign-in attempts.</li>
    </ul>
    <p>We do not sell your information, and we do not use it for advertising.</p>

    <h2>Who we share it with</h2>
    <ul>
        <li><b>Our delivery team</b> sees the delivery details for the orders they deliver.</li>
        <li><b>Paystack</b> processes payments.</li>
        <li><b>Our hosting providers</b> (Render for the website and Aiven for the database) store the data for us.</li>
    </ul>

    <h2>Cookies</h2>
    <p>We use two small cookies that the site needs to work: one keeps you logged in, and one remembers your cart, including before
        you log in. We do not use advertising or tracking cookies.</p>

    <h2>How long we keep it</h2>
    <p>We keep your account until you delete it. Orders are kept for our records even after an account is closed.</p>

    <h2>Your choices</h2>
    <ul>
        <li>You can see and change your details on your <a href="<?= url('views/account/my_account.php') ?>">My account</a> page.</li>
        <li>You can delete your account from My account → Delete account. This removes your account, saved designs and cart, and
            disconnects Google sign-in.</li>
        <li>You can also remove Packe's access from your Google account at
            <a href="https://myaccount.google.com/connections" target="_blank" rel="noopener">myaccount.google.com/connections</a>.</li>
    </ul>

    <h2>Contact</h2>
    <?php if (SUPPORT_EMAIL !== ''): ?>
        <p>Questions about your data? Email us at <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a>.</p>
    <?php else: ?>
        <p>Questions about your data? Contact the Packe team through the details on your order confirmation.</p>
    <?php endif; ?>
</article>

<?php require __DIR__ . '/layout/footer.php'; ?>
