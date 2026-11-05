<?php
/**
 * Shared header: <head>, navigation, search and cart summary. Included at the top of every view.
 * Set $pageTitle before including. No SQL here: cart numbers come from CartController.
 */
require_once __DIR__ . '/../../controllers/CartController.php';

$__cart = new CartController();
$__key = get_cart_key();
$cartCount = $__cart->getCartCount($__key);
$cartTotal = $__cart->getCartTotal($__key);
$flashSuccess = take_flash('success');
$flashError = take_flash('error');
$__here = basename($_SERVER['SCRIPT_NAME'] ?? '');
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=DM+Sans:wght@400;500;700&display=swap">
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: {
                    packe: { 50: '#fdf3ee', 100: '#fbe3d6', 500: '#d14615', 600: '#c2410c', 700: '#9a330a' },
                    ink: '#1c1a18', cream: '#fbf9f4', sand: '#f3ede3', line: '#e6dfd3', muted: '#6b635a'
                },
                fontFamily: {
                    display: ['Archivo', 'Arial Narrow', 'Arial', 'sans-serif'],
                    sans: ['DM Sans', 'Segoe UI', 'Arial', 'sans-serif']
                }
            } }
        };
    </script>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body class="bg-cream text-ink font-sans antialiased min-h-screen flex flex-col">

<header class="bg-white border-b border-line sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap items-center gap-x-6 gap-y-3">
        <a href="<?= url('index.php') ?>" class="flex items-center gap-2 shrink-0" aria-label="Packe home">
            <img src="<?= url('images/logo.svg') ?>" alt="" class="w-8 h-8">
            <span class="font-display font-extrabold text-2xl tracking-tight">Packe</span>
        </a>

        <nav class="flex items-center gap-5 text-[15px]" aria-label="Main">
            <a href="<?= url('views/all_products.php') ?>" class="<?= nav_class('all_products.php', $__here) ?>">Shop</a>
            <a href="<?= url('views/customize.php') ?>" class="<?= nav_class('customize.php', $__here) ?>">Design a box</a>
            <?php if (is_admin()): ?>
                <a href="<?= url('views/admin/dashboard.php') ?>" class="<?= nav_class('dashboard.php', $__here) ?>">Dashboard</a>
                <a href="<?= url('views/admin/orders.php') ?>" class="<?= nav_class('orders.php', $__here) ?>">Orders</a>
                <a href="<?= url('views/admin/product.php') ?>" class="<?= nav_class('product.php', $__here) ?>">Products</a>
                <a href="<?= url('views/admin/category.php') ?>" class="<?= nav_class('category.php', $__here) ?>">Categories</a>
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
            <?php if (is_logged_in()): ?>
                <span class="hidden md:inline text-muted">Hi, <?= e(explode(' ', $_SESSION['customer_name'])[0]) ?></span>
                <a href="<?= url('views/account/my_account.php') ?>" class="text-ink/80 hover:text-ink">My account</a>
                <a href="<?= url('logout.php') ?>" class="text-ink/80 hover:text-ink">Log out</a>
            <?php else: ?>
                <a href="<?= url('views/login.php') ?>" class="text-ink/80 hover:text-ink">Log in</a>
                <a href="<?= url('views/register.php') ?>" class="text-ink/80 hover:text-ink">Register</a>
            <?php endif; ?>
            <a href="<?= url('views/cart.php') ?>" class="btn btn-dark !py-2" aria-label="Cart: <?= $cartCount ?> items, <?= money($cartTotal) ?>">
                Cart <span class="tabular-nums">(<?= $cartCount ?>)</span>
                <span class="hidden sm:inline tabular-nums opacity-80">· <?= money($cartTotal) ?></span>
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
