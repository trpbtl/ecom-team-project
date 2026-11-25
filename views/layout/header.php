<?php
/**
 * Shared header: <head>, navigation, search and cart summary. Included at the top of every view.
 * Set $pageTitle before including. No SQL here: cart numbers come from CartController.
 */
require_once __DIR__ . '/../../controllers/CartController.php';
require_once __DIR__ . '/../../controllers/ProController.php';

$__cart = new CartController();
$__key = get_cart_key();
$__sum = $__cart->getCartSummary($__key);   // one database trip for the whole badge
$cartCount = $__sum['count'];
$cartTotal = $__sum['total'];
$flashSuccess = take_flash('success');
$flashError = take_flash('error');
$__here = basename($_SERVER['SCRIPT_NAME'] ?? '');
$__isPro = is_logged_in() && (new ProController())->isActive((int) $_SESSION['customer_id']);
function nav_class(string $file, string $here): string
{
    return $file === $here ? 'text-packe-600 font-semibold' : 'text-ink/80 hover:text-ink';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($pageTitle ?? 'Custom packaging') . ' | Packe') ?></title>
    <!-- Favicon: SVG for modern browsers, .ico for older ones, PNG for phone home screens -->
    <link rel="icon" href="<?= asset_url('favicon.svg') ?>" type="image/svg+xml">
    <link rel="icon" href="<?= asset_url('favicon.ico') ?>" sizes="48x48">
    <link rel="apple-touch-icon" href="<?= asset_url('apple-touch-icon.png') ?>">
    <meta name="theme-color" content="#c2410c">
    <script>
        // Light or dark: the visitor's saved choice, else their device setting. Runs before the page paints, so no flash.
        (function () {
            var saved = null;
            try { saved = localStorage.getItem('packe-theme'); } catch (e) {}
            var dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=DM+Sans:wght@400;500;700&display=swap">
    <?php if (!empty($extraHead)) echo $extraHead; /* e.g. the design fonts on the designer and home page */ ?>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: {
                    // Theme colours come from CSS variables (css/style.css), so they switch with light/dark mode
                    packe: { 50: '#fdf3ee', 100: '#fbe3d6', 500: '#d14615', 600: 'rgb(var(--packe-600) / <alpha-value>)', 700: 'rgb(var(--packe-700) / <alpha-value>)' },
                    ink: 'rgb(var(--ink-rgb) / <alpha-value>)', cream: 'rgb(var(--cream-rgb) / <alpha-value>)',
                    sand: 'rgb(var(--sand-rgb) / <alpha-value>)', line: 'rgb(var(--line-rgb) / <alpha-value>)',
                    muted: 'rgb(var(--muted-rgb) / <alpha-value>)', surface: 'rgb(var(--surface-rgb) / <alpha-value>)',
                    danger: 'rgb(var(--danger-rgb) / <alpha-value>)'
                },
                fontFamily: {
                    display: ['Archivo', 'Arial Narrow', 'Arial', 'sans-serif'],
                    sans: ['DM Sans', 'Segoe UI', 'Arial', 'sans-serif']
                }
            } }
        };
    </script>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
</head>
<body class="bg-cream text-ink font-sans antialiased min-h-screen flex flex-col">

<header class="bg-surface border-b border-line sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap items-center gap-x-6 gap-y-3">
        <a href="<?= url('index.php') ?>" class="flex items-center gap-2 shrink-0" aria-label="Packe home">
            <img src="<?= url('images/logo.svg') ?>" alt="" class="w-8 h-8">
            <span class="font-display font-extrabold text-2xl tracking-tight">Packe</span>
        </a>

        <nav class="flex items-center gap-5 text-[15px]" aria-label="Main">
            <a href="<?= url('views/all_products.php') ?>" class="<?= nav_class('all_products.php', $__here) ?>">Shop</a>
            <a href="<?= url('views/customize.php') ?>" class="<?= nav_class('customize.php', $__here) ?>">Design packaging</a>
            <?php if (!is_admin() && !is_delivery()): ?>
                <a href="<?= url('views/pro.php') ?>" class="<?= nav_class('pro.php', $__here) ?>">Packe Pro</a>
            <?php endif; ?>
            <?php if (is_admin()): ?>
                <a href="<?= url('views/admin/dashboard.php') ?>" class="<?= nav_class('dashboard.php', $__here) ?>">Dashboard</a>
                <a href="<?= url('views/admin/orders.php') ?>" class="<?= nav_class('orders.php', $__here) ?>">Orders</a>
                <a href="<?= url('views/admin/product.php') ?>" class="<?= nav_class('product.php', $__here) ?>">Products</a>
                <a href="<?= url('views/admin/category.php') ?>" class="<?= nav_class('category.php', $__here) ?>">Categories</a>
                <a href="<?= url('views/admin/businesses.php') ?>" class="<?= nav_class('businesses.php', $__here) ?>">Businesses</a>
                <a href="<?= url('views/admin/riders.php') ?>" class="<?= nav_class('riders.php', $__here) ?>">Riders</a>
                <a href="<?= url('views/admin/analytics.php') ?>" class="<?= nav_class('analytics.php', $__here) ?>">Analytics</a>
            <?php endif; ?>
            <?php if (is_delivery()): ?>
                <a href="<?= url('views/delivery/my_deliveries.php') ?>" class="<?= nav_class('my_deliveries.php', $__here) ?>">My deliveries</a>
            <?php endif; ?>
        </nav>

        <form action="<?= url('views/search_results.php') ?>" method="GET" class="flex-1 min-w-[200px] max-w-md" role="search">
            <label for="search" class="sr-only">Search products</label>
            <input id="search" name="q" type="search" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search boxes, labels, brochures…"
                   class="input w-full" minlength="2" maxlength="60">
        </form>

        <div class="flex items-center gap-4 ml-auto text-[15px]">
            <button type="button" id="theme-toggle" class="theme-toggle" aria-pressed="false" aria-label="Switch to dark mode" title="Switch to dark mode">
                <svg class="icon-moon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                <svg class="icon-sun" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            </button>
            <?php if (is_logged_in()): ?>
                <span class="hidden md:inline text-muted">Hi, <?= e(explode(' ', $_SESSION['customer_name'])[0]) ?><?php if ($__isPro): ?> <a href="<?= url('views/pro.php') ?>" class="pro-chip" title="Packe Pro member">PRO</a><?php endif; ?></span>
                <a href="<?= url('views/account/my_account.php') ?>" class="text-ink/80 hover:text-ink">My account</a>
                <a href="<?= url('logout.php') ?>" class="text-ink/80 hover:text-ink">Log out</a>
            <?php else: ?>
                <a href="<?= url('views/login.php') ?>" class="text-ink/80 hover:text-ink">Log in</a>
                <a href="<?= url('views/register.php') ?>" class="text-ink/80 hover:text-ink">Register</a>
            <?php endif; ?>
            <a href="<?= url('views/cart.php') ?>" id="cart-link" class="btn btn-dark !py-2" aria-label="Cart: <?= $cartCount ?> items, <?= money($cartTotal) ?>"
               data-count="<?= $cartCount ?>" data-total="<?= $cartTotal ?>" data-plain='<?= e(json_encode($__sum['plain_ids'])) ?>'>
                Cart <span class="tabular-nums">(<span id="cart-count"><?= $cartCount ?></span>)</span>
                <span class="hidden sm:inline tabular-nums opacity-80">· <span id="cart-total"><?= money($cartTotal) ?></span></span>
            </a>
        </div>
    </div>
</header>

<?php if ($flashSuccess || $flashError): ?>
    <div class="max-w-7xl w-full mx-auto px-4 pt-4">
        <?php if ($flashSuccess): ?><div class="alert alert-success" role="status"><?= e($flashSuccess) ?></div><?php endif; ?>
        <?php if ($flashError): ?><div class="alert alert-error" role="alert"><?= e($flashError) ?></div><?php endif; ?>
    </div>
<?php endif; ?>

<main class="flex-1 w-full max-w-7xl mx-auto px-4 py-8">
