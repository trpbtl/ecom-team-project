<?php
/**
 * Homepage
 * Layer: Presentation | Owner: Hagen Dickson
 * Introduces the company and displays featured packaging products.
 */
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <h1>Professional Custom Packaging</h1>
    <p>Design your own with pre-made templates.</p>
    <a href="pages/customize.php" class="btn">Start Designing</a>
    <a href="pages/catalogue.php" class="btn btn-outline">View Packages</a>
</section>

<section class="featured">
    <h2>Featured Products</h2>
    <!-- TODO: load featured products via ProductController::getFeatured() -->
</section>

<section class="delivery-promise">
    <h2>Our Delivery Promise</h2>
    <!-- TODO: Standard delivery, Express rush orders, Nationwide reach -->
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
