<?php
/**
 * Payment
 * Layer: Presentation | Owner: Donna Omaboe
 * Customer completes payment through the payment gateway.
 */
$pageTitle = 'Payment';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../controllers/PaymentController.php';
?>

<section>
    <h1>Payment</h1>
    <!-- TODO: Payment gateway integration (e.g. mobile money / card) -->
    <!-- TODO: Handle success / failure response -->
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
