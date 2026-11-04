<?php
/**
 * Shared page header and navigation
 * Every page sets $pageTitle before including this file.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$base = '/ecom-team-project';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Packe') ?> | Packe</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/style.css">
</head>
<body>
<header class="site-header">
    <a href="<?= $base ?>/index.php" class="logo">Packe</a>
    <nav>
        <a href="<?= $base ?>/index.php">Home</a>
        <a href="<?= $base ?>/pages/catalogue.php">Shop</a>
        <a href="<?= $base ?>/pages/customize.php">Customize</a>
        <a href="<?= $base ?>/pages/track-order.php">Track Order</a>
    </nav>
    <div class="nav-actions">
        <?php if (isset($_SESSION['customer_id'])): ?>
            <a href="<?= $base ?>/controllers/AuthController.php?action=logout">Log Out</a>
        <?php else: ?>
            <a href="<?= $base ?>/pages/login.php">Log In</a>
        <?php endif; ?>
        <a href="<?= $base ?>/pages/cart.php" class="cart">Cart</a>
    </div>
</header>
<main>
