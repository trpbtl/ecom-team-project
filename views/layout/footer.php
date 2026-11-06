</main>

<footer class="border-t border-line bg-white">
    <div class="max-w-7xl mx-auto px-4 py-8 grid gap-6 sm:grid-cols-3 text-sm text-muted">
        <div>
            <p class="font-display font-extrabold text-lg text-ink">Packe</p>
            <p class="mt-1">Custom packaging for businesses across Ghana, delivered by our own team.</p>
        </div>
        <div>
            <p class="font-semibold text-ink">Delivery</p>
            <p class="mt-1">Standard delivery for regular orders. Express rush delivery when you need it fast.</p>
        </div>
        <div>
            <p class="font-semibold text-ink">Payments</p>
            <p class="mt-1">Mobile money and card payments through Paystack (test mode in this demo).</p>
        </div>
    </div>
    <p class="text-center text-xs text-muted pb-6">&copy; <?= date('Y') ?> Packe · E-commerce team project</p>
</footer>

<script src="<?= asset_url('js/validate.js') ?>"></script>
<script src="<?= asset_url('js/cart.js') ?>"></script>
<?php if (!empty($extraScripts)) echo $extraScripts; ?>
</body>
</html>
